<?php

namespace App\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use App\Entity\RequestForComment;
use App\Entity\Event;
use App\Model\Vote;
use App\Model\Votes;

use Buzz\Browser;
use Buzz\Client\Curl;
use Symfony\Component\CssSelector\CssSelectorConverter;

class SynchronizeVotesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('php-rfc-watch:synchronize')
            ->setDescription('Synchronize the Current votes from wiki.php.net to RFC Watch')
            ->addArgument('urls', InputArgument::IS_ARRAY)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $rfcRepository = $entityManager->getRepository(RequestForComment::CLASS);

        $rfcs = [];
        foreach ($rfcRepository->findAll() as $rfc) {
            $rfcs[$rfc->getVoteId()] = $rfc;
        }

        $curl = new Curl();
        $curl->setOption(CURLOPT_TIMEOUT, 15);
        $browser = new Browser($curl);

        if ($input->getArgument('urls')) {
            $rfcUrls = $input->getArgument('urls');
        } else {
            $rfcUrls = $this->getRfcsInVoting($browser);

            $rfcUrls = array_filter(array_unique(
                array_merge(
                    $rfcUrls,
                    array_map(
                        function ($rfc) { return $rfc->getUrl(); },
                        array_filter($rfcs, function ($rfc) { return $rfc->isOpen(); })
                    )
                )
            ));
        }

        foreach ($rfcUrls as $rfcUrl) {
            $response = $browser->get($rfcUrl);

            $html = $response->getContent();
            $dom = $response->toDomDocument();
            $xpath = new \DOMXpath($dom);

            $nodes = $xpath->evaluate('//form[@name="doodle__form"]');

            foreach ($nodes as $form) {
                $output->writeln(sprintf('Found Form for <info>%s</info>', $rfcUrl));
                $rows = $xpath->evaluate('table[@class="inline"]/tbody/tr', $form);
                $voteId = $form->getAttribute('id');
                $question = "";

                $votes = array();
                $voteWasClosed = false;

                $options = array();
                foreach ($rows as $row) {
                    switch ((string)$row->getAttribute('class')) {
                        case 'row0':
                            $question = trim($xpath->evaluate('string(th/text())', $row));
                            // do nothing;
                            break;

                        case 'row1':
                            foreach ($xpath->evaluate('td', $row) as $optionNode) {
                                $option = trim($optionNode->nodeValue);
                                if ($option !== "Real name") {
                                    $options[] = $option;
                                }
                            }
                            break;

                        default:
                            $username = trim($xpath->evaluate('string(td[1])', $row));

                            if ($username === 'This poll has been closed.') {
                                $voteWasClosed = true;
                                break;
                            }

                            if (!preg_match('(\(([^\)]+)\))', $username, $matches)) {
                                break;
                            }

                            $username = md5($matches[1]);
                            $time = new \DateTime;

                            $option = -1;
                            foreach ($xpath->evaluate('td', $row) as $optionNode) {
                                if ($optionNode->getAttribute('style') == 'background-color:#AFA') {
                                    $imgTitle = $xpath->evaluate('img[@title]', $optionNode);
                                    if ($imgTitle && $imgTitle->length > 0) {
                                        $time = \DateTime::createFromFormat('Y/m/d H:i', $imgTitle->item(0)->getAttribute('title'), new \DateTimeZone('UTC'));
                                        $time->modify('-60 minute'); // hardcode how far both servers are away from each other timezone-wise
                                    }
                                    break;
                                }
                                $option++;
                            }
                            $votes[$username] = new Vote($options[$option], $time);
                            break;
                    }
                }

                $votes = new Votes($votes);

                if (!isset($rfcs[$voteId])) {
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
                    $rfc->setQuestion($question);
                    $rfc->setAuthor($author);
                    $rfc->setVoteId($voteId);

                    if (strpos($html, "2/3 majority")) {
                        $rfc->setPassThreshold(66);
                    }

                    $rfcs[$voteId] = $rfc;

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

                    $entityManager->persist(new Event($rfc, 'VoteOpened', null, $start));
                    $entityManager->persist($rfc);
                } else {
                    $rfc = $rfcs[$voteId];
                }

                $changedVotes = $votes->diff($rfc->getVotes());

                foreach ($changedVotes->getNewVotes() as $username => $vote) {
                    $entityManager->persist(new Event($rfc, 'UserVoted', $vote->getOption(), $vote->getTime()));
                }

                foreach ($changedVotes->getRemovedVotes() as $username => $option) {
                    $entityManager->persist(new Event($rfc, 'UserVoteRemoved', $vote->getOption(), $vote->getTime()));
                }

                $rfc->setVotes($votes);
                $rfc->setVoteId($voteId);
                $rfc->setQuestion($question);

                if ($voteWasClosed && $rfc->isOpen()) {
                    $rfc->closeVote();
                    $entityManager->persist(new Event($rfc, 'VoteClosed', null, new \DateTime('now')));
                }

                $output->writeln(sprintf('..Found <info>%d</info> changes in votes.', count($changedVotes)));
            }
        }

        $entityManager->flush();
    }

    private function getRfcsInVoting(Browser $browser)
    {
        $converter  = new CssSelectorConverter;
        $response   = $browser->get('https://wiki.php.net/rfc');
        $document   = $response->toDomDocument();
        $xPath      = new \DOMXPath($document);
        $rfcs       = [];

        foreach ($xPath->query($converter->toXPath('#in_voting_phase + .level2 .li')) as $listing) {
            /** @var \DOMNode $listing */
            /** @var \DOMElement $link */
            $link   = $xPath->query($converter->toXPath('a'), $listing)->item(0);
            $rfcs[] = $link->getAttribute('href');
        }

        return array_map(function ($link) {
            return 'https://wiki.php.net' . $link;
        }, $rfcs);
    }
}
