<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use AppBundle\CouchDocument\RequestForComment;
use AppBundle\CouchDocument\Event;

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
        $documentManager = $this->get('doctrine_couchdb.odm.default_document_manager');
        $rfcRepository = $documentManager->getRepository(RequestForComment::CLASS);
        $eventRepository = $documentManager->getRepository(Event::CLASS);

        $rfcs = $rfcRepository->findAll();
        $events = $eventRepository = $eventRepository->findAll();

        $result = [];

        foreach ($rfcs as $rfc) {
            $result['rfcs'][] = [
                'id' => $rfc->getId(),
                'title' => $rfc->getTitle(),
                'url' => $rfc->getUrl(),
                'results' => $rfc->getCurrentResults(),
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
            $result['events'][] = [
                'type' => $event->getType(),
                'option' => $event->getOption(),
                'user' => $event->getUser(),
                'vote' => ['id' => $event->getRfc()->getId(), 'title' => $event->getRfc()->getTitle(), 'url' => $event->getRfc()->getUrl()],
                'date' => $event->getDate()->format('Y-m-d H:i:s'),
            ];
        }

        return new JsonResponse($result);
    }
}
