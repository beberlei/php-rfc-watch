<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use AppBundle\CouchDocument\RequestForComment;
use AppBundle\CouchDocument\Event;
use AppBundle\Model\Votes;

use Buzz\Browser;
use Buzz\Client\Curl;

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
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $rfcs = [];
        foreach ($rfcRepository->findAll() as $rfc) {
            $rfcs[$rfc->getUrl()] = $rfc;
        }

        $rfcUrls = array('https://wiki.php.net/rfc/scalar_type_hints_v5');

        $curl = new Curl();
        $curl->setOption(CURLOPT_TIMEOUT, 15);
        $browser = new Browser($curl);

        $newEvents = [];

        foreach ($rfcUrls as $rfcUrl) {
            $response = $browser->get($rfcUrl);

            $dom = $response->toDomDocument();
            $xpath = new \DOMXpath($dom);

            $nodes = $xpath->evaluate('//form[@name="doodle__form"]');

            foreach ($nodes as $form) {
                $output->writeln(sprintf('Found Form for <info>%s</info>', $rfcUrl));
                $rows = $xpath->evaluate('table[@class="inline"]/tbody/tr', $form);

                $votes = array();
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

                            $option = -1;
                            foreach ($xpath->evaluate('td', $row) as $optionNode) {
                                if ($optionNode->getAttribute('style') == 'background-color:#AFA') {
                                    break;
                                }
                                $option++;
                            }
                            $votes[$username] = $options[$option];
                            break;
                    }
                }
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

                $documentManager->persist(new Event($rfc, 'VoteOpened', $author, null, $now));
                $documentManager->persist($rfc);
            }

            $changedVotes = $votes->diff($rfc->getVotes());

            foreach ($changedVotes->getNewVotes() as $username => $option) {
                $documentManager->persist(new Event($rfc, 'UserVoted', $username, $option, $now));
            }

            foreach ($changedVotes->getRemovedVotes() as $username => $option) {
                $documentManager->persist(new Event($rfc, 'UserVoteRemoved', $username, $option, $now));
            }

            $rfc->setVotes($votes);

            $output->writeln(sprintf('..Found <info>%d</info> changes in votes.', count($changedVotes)));
        }

        $documentManager->flush();
    }
}
