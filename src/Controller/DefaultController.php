<?php

namespace App\Controller;

use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use App\Entity\RequestForComment;
use App\Entity\Event;
use Zend\Feed\Writer\Feed;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        return [];
    }

    /**
     * @Route("/data.json", name="data")
     */
    public function dataAction()
    {
        $entityManager = $this->get('doctrine.orm.default_entity_manager');
        $rfcRepository = $entityManager->getRepository(RequestForComment::CLASS);
        $eventRepository = $entityManager->getRepository(Event::CLASS);

        $rfcs = array_reverse($rfcRepository->findAll());
        $events = $eventRepository = $eventRepository->findAll();

        $result = [];

        foreach ($rfcs as $rfc) {
            $result['rfcs'][] = [
                'id' => $rfc->getId(),
                'title' => $rfc->getTitle(),
                'url' => $rfc->getUrl(),
                'results' => $rfc->getCurrentResults(),
                'status' => $rfc->getStatus(),
                'share' => $rfc->getYesShare(),
            ];
        }

        usort($events, function ($a, $b) {
            $a = $a->getDate()->format('U');
            $b = $b->getDate()->format('U');

            if ($a == $b) {
                return 0;
            }

            return $a > $b ? -1 : 1;
        });

        foreach ($events as $event) {
            $vote = [
                'id' => $event->getRfc()->getId(),
                'title' => $event->getRfc()->getTitle(),
                'url' => $event->getRfc()->getUrl()
            ];
            // TODO: Cleanup somehow
            if ($event->getType() == 'VoteClosed') {
                $vote['results'] = $event->getRfc()->getCurrentResults();
            }
            $result['events'][] = [
                'type' => $event->getType(),
                'option' => $event->getOption(),
                'vote' => $vote,
                'date' => $event->getDate()->format(DateTime::ISO8601),
            ];
        }

        return new JsonResponse($result);
    }

    /**
     * @Route("/notify", name="notify")
     */
    public function newsletterAction()
    {
        return [];
    }

    /**
     * @Route("/optin", name="optin")
     */
    public function optinAction()
    {
        return [];
    }

    /**
     * @Route("/confirm", name="confirm")
     */
    public function confirmAction()
    {
        return [];
    }

    /**
     * @Route("/unsubscribe", name="unsubscribe")
     */
    public function unsubscribeAction()
    {
        return [];
    }

    /**
     * @Route("/atom.xml", name="atom")
     */
    public function atomAction()
    {
        $entityManager = $this->get('doctrine.orm.default_entity_manager');
        $rfcRepository = $entityManager->getRepository(RequestForComment::CLASS);

        $rfcs = $rfcRepository->findBy(['status' => 'close'], ['closeDate' => 'DESC'], 10);

        $feed = new Feed;
        $feed->setTitle("PHP RFC Watch");
        $feed->setLink('https://php-rfc-watch.beberlei.de');
        $feed->setFeedLink('https://php-rfc-watch.beberlei.de/atom.xml', 'atom');
        $feed->addAuthor([
            'name'  => 'Benjamin',
            'email' => 'benjamin@tideways.io',
            'uri'   => 'https://tideways.com',
        ]);

        $modifiedDateSet = false;

        foreach ($rfcs as $rfc) {
            if (!$rfc->getCloseDate()) {
                continue;
            }

            $results = $rfc->getCurrentResults();
            $content = sprintf("%d %%\n", $rfc->getYesShare());

            foreach ($results as $result) {
                $content .= sprintf("%s: %d votes\n", $result['option'], $result['votes']);
            }

            if (!$modifiedDateSet) {
                $feed->setDateModified((int)$rfc->getCloseDate()->format('U'));
                $modifiedDateSet = true;
            }

            $entry = $feed->createEntry();
            $entry->setTitle($rfc->getTitle());
            $entry->setLink($rfc->getUrl());
            $entry->setDateModified((int)$rfc->getCloseDate()->format('U'));
            $entry->setDateCreated((int)$rfc->getCloseDate()->format('U'));
            $entry->setDescription($content);
            $entry->setContent($content);

            $feed->addEntry($entry);
        }

        if (!$modifiedDateSet) {
            $feed->setDateModified(time());
        }

        return new Response($feed->export('atom'), 200, ['Content-Type' => 'application/atom+xml']);
    }
}
