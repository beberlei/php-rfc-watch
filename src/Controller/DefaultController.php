<?php

namespace App\Controller;

use App\Entity\Rfc;
use App\Entity\Vote;
use App\Form\RfcType;
use QafooLabs\MVC\FormRequest;
use QafooLabs\MVC\RedirectRoute;
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
     * @Route("/admin", name="admin")
     */
    public function adminAction()
    {
        $rfcRepository = $this->entityManager->getRepository(Rfc::CLASS);
        $rfcs = array_reverse($rfcRepository->findAll());

        return ['rfcs' => $rfcs];
    }

    /**
     * @Route("/admin/rfc/{id}", name="admin_edit_rfc", methods={"POST", "GET"})
     */
    public function adminEditRfcAction(Rfc $rfc, FormRequest $request)
    {
        if (!$request->handle(RfcType::class, $rfc)) {
            return ['rfc' => $rfc, 'form' => $request->createFormView()];
        }

        $this->entityManager->flush();

        return new RedirectRoute('admin');
    }

    /**
     * @Route("/admin/rfc/{id}/export", name="admin_export_rfc", methods={"GET"})
     */
    public function adminExportRfcAction(Rfc $rfc)
    {
        return ['rfc' => $rfc];
    }

    /**
     * @Route("/data.json", name="data")
     */
    public function dataAction()
    {
        $rfcRepository = $this->entityManager->getRepository(Rfc::CLASS);

        $rfcs = array_reverse($rfcRepository->findAll());

        $data = [];
        foreach ($rfcs as $rfc) {
            assert($rfc instanceof Rfc);

            $questions = array_map(function (Vote $vote) {
                $data = ['question' => $vote->question, 'results' => [], 'hasYes' => false, 'passing' => false];

                $total = array_sum($vote->currentVotes);

                foreach ($vote->currentVotes as $option => $count) {
                    $data['results'][] = [
                        'votes' => $count,
                        'share' => $count / $total,
                        'option' => $option,
                    ];

                    if ($option === "Yes") {
                        $data['hasYes'] = true;

                        if ($count / $total >= $vote->passThreshold/100) {
                            $data['passing'] = true;
                        }
                    }
                }

                return $data;
            }, $rfc->votes->toArray());

            $data[] = [
                'id' => $rfc->id,
                'title' => $rfc->title,
                'url' => $rfc->url,
                'status' => $rfc->status,
                'targetPhpVersion' => $rfc->targetPhpVersion,
                'discussions' => $rfc->discussions,
                'questions' => array_values($questions),
                'rejected' => $rfc->rejected,
            ];
        }

        $result = [];
        $result['rejected'] = array_values(array_filter($data, function ($item) { return $item['rejected']; }));
        $result['active'] = array_values(array_filter($data, function ($item) { return $item['status'] === 'open' && !$item['rejected']; }));
        $others = array_values(array_filter($data, function ($item) { return $item['status'] !== 'open' && !$item['rejected']; }));

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
