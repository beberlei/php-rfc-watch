<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use App\Entity\RequestForComment;
use Zend\Feed\Writer\Feed;
use Doctrine\ORM\EntityManagerInterface;

class DefaultController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

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
        $rfcRepository = $this->entityManager->getRepository(RequestForComment::CLASS);

        $rfcs = array_reverse($rfcRepository->findAll());

        $aggregated = [];

        foreach ($rfcs as $rfc) {
            assert($rfc instanceof RequestForComment);

            if (!isset($aggregated[$rfc->getUrl()])) {
                $aggregated[$rfc->getUrl()] = [
                    'id' => $rfc->getId(),
                    'title' => $rfc->getTitle(),
                    'url' => $rfc->getUrl(),
                    'status' => $rfc->getStatus(),
                    'targetPhpVersion' => $rfc->getTargetPhpVersion() ?: 'unknown',
                    'questions' => [],
                ];
            }

            $aggregated[$rfc->getUrl()]['questions'][] = [
                'question' => $rfc->getQuestion(),
                'results' => $rfc->getCurrentResults(),
                'share' => $rfc->getYesShare(),
            ];
        }

        $aggregated = array_values($aggregated);

        $result['active'] = array_values(array_filter($aggregated, function ($item) { return $item['status'] === 'open'; }));
        $others = array_values(array_filter($aggregated, function ($item) { return $item['status'] !== 'open'; }));

        $result['others'] = ['unknown' => []];

        foreach ($others as $other) {
            if (!isset($result['others'][$other['targetPhpVersion']])) {
                $result['others'][$other['targetPhpVersion']] = [];
            }
            $result['others'][$other['targetPhpVersion']][] = $other;
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
