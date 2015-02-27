<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use AppBundle\CouchDocument\RequestForComment;
use AppBundle\CouchDocument\Event;
use AppBundle\Model\Vote;
use AppBundle\Model\Votes;

use Buzz\Browser;
use Buzz\Client\Curl;
use Symfony\Component\CssSelector\CssSelector;

class SynchronizeVotesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rfc-watch:synchronize')
            ->setDescription('Synchronize the Current votes from wiki.php.net to RFC Watch')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $documentManager = $this->getContainer()->get('doctrine_couchdb.odm.default_document_manager');
        $rfcRepository = $documentManager->getRepository(RequestForComment::CLASS);

        $rfcs = [];
        foreach ($rfcRepository->findAll() as $rfc) {
            $rfcs[$rfc->getUrl()] = $rfc;
        }

        $curl = new Curl();
        $curl->setOption(CURLOPT_TIMEOUT, 15);
        $browser = new Browser($curl);

        $rfcUrls = $this->getRfcsInVoting($browser);

        $newEvents = [];

        foreach ($rfcUrls as $rfcUrl) {
            $response = $browser->get($rfcUrl);

            $dom = $response->toDomDocument();
            $xpath = new \DOMXpath($dom);

            $nodes = $xpath->evaluate('//form[@name="doodle__form"]');
            $votes = array();

            foreach ($nodes as $form) {
                $output->writeln(sprintf('Found Form for <info>%s</info>', $rfcUrl));
                $rows = $xpath->evaluate('table[@class="inline"]/tbody/tr', $form);

                foreach ($rows as $row) {
                    switch ((string)$row->getAttribute('class')) {
                        case 'row0':
                            // do nothing;
                            break;
                        case 'row1':
                            $options = array();
                            foreach ($xpath->evaluate('td', $row) as $optionNode) {
                                $option = trim($optionNode->nodeValue);
                                if ($option !== "Real name") {
                                    $options[] = $option;
                                }
                            }
                            break;
                        default:
                            $username = trim($xpath->evaluate('string(td[1])', $row));

                            if (!preg_match('(\(([^\)]+)\))', $username, $matches)) {
                                continue;
                            }
                            $username = $matches[1];
                            $time = new \DateTime;

                            $option = -1;
                            foreach ($xpath->evaluate('td', $row) as $optionNode) {
                                if ($optionNode->getAttribute('style') == 'background-color:#AFA') {
                                    $imgTitle = $xpath->evaluate('img[@title]', $optionNode);
                                    if ($imgTitle && $imgTitle->length > 0) {
                                        $time = \DateTime::createFromFormat('Y/m/d H:i', $imgTitle->item(0)->getAttribute('title'), new \DateTimeZone('UTC'));
                                    }
                                    break;
                                }
                                $option++;
                            }
                            $votes[$username] = new Vote($options[$option], $time);
                            break;
                    }
                }

                break; // only one form!
            }

            $votes = new Votes($votes);

            if (!isset($rfcs[$rfcUrl])) {
                $title = trim(str_replace('PHP RFC:', '', $xpath->evaluate('string(//h1)')));
                $author = "";

                $listItems = $xpath->evaluate('//li/div[@class="li"]');
                foreach ($listItems as $listItem) {
                    $content = trim($listItem->nodeValue);
                    if (strpos($content, "Author") === 0) {
                        $parts = explode(":", $content);
                        $author = $parts[1];
                    }
                }

                $rfc = new RequestForComment();
                $rfc->setTitle($title);
                $rfc->setUrl($rfcUrl);
                $rfc->setAuthor($author);
                $rfcs[$rfcUrl] = $rfc;

                // Guess at the approximate start time based on the first vote.
                $start = array_reduce(iterator_to_array($votes), function (\DateTime $start, Vote $vote) {
                    if ($start > $vote->getTime()) {
                        return clone $vote->getTime();
                    }
                    return $start;
                }, new \DateTime);

                // Subtract another minute so the vote opening always appears
                // before the first vote.
                $start->sub(new \DateInterval('PT1M'));

                $documentManager->persist(new Event($rfc, 'VoteOpened', $author, null, $start));
                $documentManager->persist($rfc);
            } else {
                $rfc = $rfcs[$rfcUrl];
            }

            $changedVotes = $votes->diff($rfc->getVotes());

            foreach ($changedVotes->getNewVotes() as $username => $vote) {
                $documentManager->persist(new Event($rfc, 'UserVoted', $username, $vote->getOption(), $vote->getTime()));
            }

            foreach ($changedVotes->getRemovedVotes() as $username => $option) {
                $documentManager->persist(new Event($rfc, 'UserVoteRemoved', $username, $vote->getOption(), $vote->getTime()));
            }

            $rfc->setVotes($votes);

            $output->writeln(sprintf('..Found <info>%d</info> changes in votes.', count($changedVotes)));
        }

        $documentManager->flush();
    }

    private function getRfcsInVoting(Browser $browser)
    {
        $response   = $browser->get('https://wiki.php.net/rfc');
        $document   = $response->toDomDocument();
        $xPath      = new \DOMXPath($document);
        $rfcs       = [];
        foreach ($xPath->query(CssSelector::toXPath('#in_voting_phase + .level2 .li')) as $listing) {
            /** @var \DOMNode $listing */
            /** @var \DOMElement $link */
            $link   = $xPath->query(CssSelector::toXPath('a'), $listing)->item(0);
            $rfcs[] = $link->getAttribute('href');
        }

        return array_map(function ($link) {
            return 'https://wiki.php.net' . $link;
        }, $rfcs);
    }
}
