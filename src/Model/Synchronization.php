<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Rfc;
use App\Repository\RfcRepository;
use Symfony\Component\CssSelector\CssSelectorConverter;

class Synchronization
{
    private RfcRepository $rfcRepository;
    private RfcDomFetcher $rfcFetcher;

    public function __construct(RfcRepository $rfcRepository, RfcDomFetcher $rfcFetcher)
    {
        $this->rfcRepository = $rfcRepository;
        $this->rfcFetcher = $rfcFetcher;
    }

    /** @return list<string> */
    public function getRfcUrlsInVoting(): array
    {
        $converter  = new CssSelectorConverter();
        $document = $this->rfcFetcher->getRfcDom('https://wiki.php.net/rfc');
        $xPath      = new \DOMXPath($document);
        $rfcs       = [];

        foreach ($xPath->query($converter->toXPath('#in_voting_phase + .level2 .li')) as $listing) {
            \assert($listing instanceof \DOMNode);
            $link = $xPath->query($converter->toXPath('a'), $listing)->item(0);
            \assert($link instanceof \DOMElement);
            $rfcs[] = $link->getAttribute('href');
        }

        $currentInVotingUrls = array_map(static fn ($link) => 'https://wiki.php.net' . $link, $rfcs);

        $ourActiveRfcs = $this->rfcRepository->findActiveRfcs();

        $activeRfcUrls = array_map(static fn (Rfc $rfc) => $rfc->url, $ourActiveRfcs);

        return array_unique(array_merge($currentInVotingUrls, $activeRfcUrls));
    }

    /**
     * @param list<string> $rfcUrls
     *
     * @return list<Rfc>
     */
    public function synchronizeRfcs(array $rfcUrls, ?string $targetPhpVersion = null): array
    {
        $rfcs = [];

        foreach ($rfcUrls as $rfcUrl) {
            $rfcs[] = $this->synchronizeRfc($rfcUrl, $targetPhpVersion);
        }

        $this->rfcRepository->flush();

        return $rfcs;
    }

    private function synchronizeRfc(string $rfcUrl, ?string $targetPhpVersion = null): Rfc
    {
        $matches = [];
        $rfc = $this->rfcRepository->findOneByUrl($rfcUrl);

        if (! $rfc) {
            $rfc = new Rfc();
            $rfc->url = $rfcUrl;
        }

        $dom = $this->rfcFetcher->getRfcDom($rfcUrl);
        $content = $dom->saveHTML();
        $xpath = new \DOMXpath($dom);

        $rfc->title = trim(str_replace('PHP RFC:', '', $xpath->evaluate('string(//h1)')));

        if (empty($rfc->targetPhpVersion)) {
            $targetVersionRegexps = [
                '(Targets: ([^<]+))',
                '(Proposed version: ([^<]+))',
                '(Proposed Version: ([^<]+))',
                '(Target version: ([^<]+))',
                '(Target Version: ([^<]+))',
            ];

            // set default to the one passed potentially via commandline flag
            $rfc->targetPhpVersion = (string) $targetPhpVersion;

            foreach ($targetVersionRegexps as $targetVersionRegexp) {
                if (! preg_match($targetVersionRegexp, $content, $matches)) {
                    continue;
                }

                $rfc->targetPhpVersion = trim(str_replace('PHP', '', $matches[1]));
            }
        }

        $nodes = $xpath->evaluate('//form[@name="doodle__form"]');

        if ($nodes->length === 0) {
            // Do not store the RFC if it has not been persistet yet, there was no vote form found.
            // maybe temporary breakage of the wiki page.
            return $rfc;
        }

        $first = true;
        foreach ($nodes as $form) {
            $rows = $xpath->evaluate('table[@class="inline"]/tbody/tr', $form);
            $voteId = $this->extractVoteId($form);

            $vote = $rfc->getVoteById($voteId);

            $vote->primaryVote = $first;
            $first = false;

            $options = [];
            foreach ($rows as $row) {
                switch ((string) $row->getAttribute('class')) {
                    case 'row0':
                        $vote->question = trim($xpath->evaluate('string(th/text())', $row));
                        // do nothing;
                        break;

                    case 'row1':
                        foreach ($xpath->evaluate('td', $row) as $optionNode) {
                            $option = trim($optionNode->nodeValue);
                            if ($option === 'Real name') {
                                continue;
                            }

                            $options[] = $option;
                            $vote->currentVotes[$option] = 0;
                        }

                        break;

                    default:
                        $firstColumn = trim($xpath->evaluate('string(td[1])', $row));

                        if ($firstColumn === 'This poll has been closed.') {
                            $rfc->status = Rfc::CLOSE;
                            break;
                        }

                        if (! preg_match('(\(([^\)]+)\))', $firstColumn, $matches)) {
                            break;
                        }

                        $option = -1;
                        foreach ($xpath->evaluate('td', $row) as $optionNode) {
                            if ($optionNode->getAttribute('style') === 'background-color:#AFA') {
                                $imgTitle = $xpath->evaluate('img[@title]', $optionNode);
                                if ($imgTitle && $imgTitle->length > 0) {
                                    $time = \DateTime::createFromFormat(
                                        'Y/m/d H:i',
                                        $imgTitle->item(0)->getAttribute('title'),
                                        new \DateTimeZone('UTC')
                                    );
                                    // hardcode how far both servers are away from each other timezone-wise
                                    $time->modify('-60 minute');

                                    if ($rfc->firstVote > $time) {
                                        $rfc->firstVote = $time;
                                    }
                                }

                                break;
                            }

                            $option++;
                        }

                        $vote->currentVotes[$options[$option]]++;
                        break;
                }
            }

            $rfc->rejected = false;

            if ($rfc->status !== Rfc::CLOSE || ! $vote->primaryVote || ! isset($vote->currentVotes['Yes'])) {
                continue;
            }

            $yesShare = $vote->currentVotes['Yes'] / array_sum($vote->currentVotes);
            if ($yesShare >= $vote->passThreshold / 100) {
                continue;
            }

            $rfc->rejected = true;
        }

        $this->rfcRepository->persist($rfc);

        return $rfc;
    }

    private function extractVoteId(\DOMElement $form): string
    {
        return str_replace(['.'], '', $form->getAttribute('id'));
    }
}
