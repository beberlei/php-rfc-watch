<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Rfc;
use App\Entity\Vote;
use App\Form\RfcType;
use App\Model\MercurePublisher;
use Doctrine\ORM\EntityManagerInterface;
use Gyro\MVC\FormRequest;
use Gyro\MVC\RedirectRoute;
use Predis\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Zend\Feed\Writer\Feed;

class DefaultController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private Client $redis;
    private MercurePublisher $mercurePublisher;

    public function __construct(
        EntityManagerInterface $entityManager,
        Client $redis,
        MercurePublisher $mercurePublisher
    ) {
        $this->entityManager = $entityManager;
        $this->redis = $redis;
        $this->mercurePublisher = $mercurePublisher;
    }

    /**
     * @return array<string,mixed>
     *
     * @Route("/", name="homepage")
     */
    public function indexAction(): array
    {
        return [];
    }

    /**
     * @return array<string,mixed>
     *
     * @Route("/admin", name="admin")
     */
    public function adminAction(): array
    {
        $rfcRepository = $this->entityManager->getRepository(Rfc::CLASS);
        $rfcs = array_reverse($rfcRepository->findAll());

        return ['rfcs' => $rfcs];
    }

    /**
     * @return array<string,mixed>|RedirectRoute
     *
     * @Route("/admin/rfc/{id}", name="admin_edit_rfc", methods={"POST", "GET"})
     */
    public function adminEditRfcAction(Rfc $rfc, FormRequest $request)
    {
        if (! $request->handle(RfcType::class, $rfc)) {
            return ['rfc' => $rfc, 'form' => $request->createFormView()];
        }

        $this->entityManager->flush();

        return new RedirectRoute('admin');
    }

    /**
     * @return array<string,mixed>
     *
     * @Route("/admin/rfc/{id}/export", name="admin_export_rfc", methods={"GET"})
     */
    public function adminExportRfcAction(Rfc $rfc): array
    {
        return ['rfc' => $rfc];
    }

    /**
     * @Route("/data.json", name="data")
     */
    public function dataAction(Request $request): JsonResponse
    {
        $githubUserId = $request->getSession()->get('github_user_id');

        $rfcRepository = $this->entityManager->getRepository(Rfc::CLASS);

        $rfcs = array_reverse($rfcRepository->findBy([], ['targetPhpVersion' => 'ASC']));

        $data = [];
        foreach ($rfcs as $rfc) {
            assert($rfc instanceof Rfc);

            $questions = array_map(static function (Vote $vote) {
                $data = ['question' => $vote->question, 'results' => [], 'hasYes' => false, 'passing' => false];

                $total = array_sum($vote->currentVotes);

                foreach ($vote->currentVotes as $option => $count) {
                    $data['results'][] = [
                        'votes' => $count,
                        'share' => $total > 0 ? $count / $total : 0,
                        'option' => $option,
                    ];

                    if ($option !== 'Yes') {
                        continue;
                    }

                    $data['hasYes'] = true;

                    if ($count / $total < $vote->passThreshold / 100) {
                        continue;
                    }

                    $data['passing'] = true;
                }

                return $data;
            }, $rfc->votes->filter(static fn (Vote $vote) => ! $vote->hide)->toArray());

            $yourVote = $githubUserId ? (int) $this->redis->zscore('rfc/' . $rfc->id, $githubUserId) : 0;

            $data[] = [
                'id' => $rfc->id,
                'title' => $rfc->title,
                'url' => $rfc->url,
                'status' => $rfc->status,
                'targetPhpVersion' => $rfc->targetPhpVersion,
                'discussions' => $rfc->discussions,
                'questions' => array_values($questions),
                'rejected' => $rfc->rejected,
                'communityVote' => [
                    'up' => $this->redis->zcount('rfc/' . $rfc->id, 1, 1),
                    'down' => $this->redis->zcount('rfc/' . $rfc->id, -1, -1),
                    'you' => $yourVote,
                ],
            ];
        }

        $activeFilter = static fn ($item) => $item['status'] === 'open' && ! $item['rejected'];
        $othersFilter = static fn ($item) => $item['status'] !== 'open' && ! $item['rejected'];

        $result = ['logged_in' => $request->getSession()->has('github_user_id')];
        $result['rejected'] = array_values(array_filter($data, static fn ($item) => $item['rejected']));
        $result['active'] = array_values(array_filter($data, $activeFilter));
        $others = array_values(array_filter($data, $othersFilter));

        $result['others'] = ['unknown' => []];

        foreach ($others as $other) {
            if (! isset($result['others'][$other['targetPhpVersion']])) {
                $result['others'][$other['targetPhpVersion']] = [];
            }

            $result['others'][$other['targetPhpVersion']][] = $other;
        }

        return new JsonResponse($result);
    }

    /**
     * @Route("/vote")
     */
    public function voteAction(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $payload = json_decode($request->getContent(), true);

        $rfc = $this->entityManager->find(Rfc::class, $payload['id']);
        $githubUserId = $session->get('github_user_id');

        if ($rfc && $githubUserId) {
            $this->redis->zadd('rfc/' . $rfc->id, [$githubUserId => $payload['choice']]);
        }

        $yourVote = $githubUserId ? (int) $this->redis->zscore('rfc/' . $rfc->id, $githubUserId) : 0;

        $this->mercurePublisher->publish('vote', ['rfc' => $rfc->id]);

        return new JsonResponse([
            'communityVote' => [
                'up' => $this->redis->zcount('rfc/' . $rfc->id, 1, 1),
                'down' => $this->redis->zcount('rfc/' . $rfc->id, -1, -1),
                'you' => $yourVote,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     *
     * @Route("/notify", name="notify")
     */
    public function newsletterAction(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     *
     * @Route("/optin", name="optin")
     */
    public function optinAction(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     *
     * @Route("/confirm", name="confirm")
     */
    public function confirmAction(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     *
     * @Route("/unsubscribe", name="unsubscribe")
     */
    public function unsubscribeAction(): array
    {
        return [];
    }

    /**
     * @Route("/atom.xml", name="atom")
     */
    public function atomAction(): Response
    {
        $rfcRepository = $this->entityManager->getRepository(Rfc::CLASS);

        $rfcs = $rfcRepository->findBy(['status' => 'close'], ['id' => 'DESC'], 10);

        $feed = new Feed();
        $feed->setTitle('PHP RFC Watch');
        $feed->setLink('https://php-rfc-watch.beberlei.de');
        $feed->setFeedLink('https://php-rfc-watch.beberlei.de/atom.xml', 'atom');
        $feed->addAuthor([
            'name'  => 'Benjamin',
            'email' => 'benjamin@tideways.io',
            'uri'   => 'https://tideways.com',
        ]);

        $modifiedDateSet = false;

        foreach ($rfcs as $rfc) {
            assert($rfc instanceof Rfc);

            $content = 'URL: ' . $rfc->url . "\n\n";

            if (count($rfc->discussions) > 0) {
                $content .= "## Discussions\n\n";

                foreach ($rfc->discussions as $discussion) {
                    $content .= '- ' . $discussion . "\n";
                }

                $content .= "\n";
            }

            $content .= "## Votes\n\n";

            foreach ($rfc->votes as $vote) {
                assert($vote instanceof Vote);

                $content .= sprintf("### %s\n\n", $vote->question);

                foreach ($vote->currentVotes as $option => $count) {
                    $content .= sprintf("- %s with %d votes\n", $option, $count);
                }

                $content .= "\n";
            }

            if (! $modifiedDateSet) {
                $feed->setDateModified((int) $rfc->created->format('U'));
                $modifiedDateSet = true;
            }

            $entry = $feed->createEntry();
            $entry->setTitle($rfc->title);
            $entry->setLink($rfc->url);
            $entry->setDateModified((int) $rfc->created->format('U'));
            $entry->setDateCreated((int) $rfc->created->format('U'));
            $entry->setDescription(strip_tags($content));
            $entry->setContent(strip_tags($content));

            $feed->addEntry($entry);
        }

        if (! $modifiedDateSet) {
            $feed->setDateModified(time());
        }

        return new Response($feed->export('atom'), 200, ['Content-Type' => 'application/atom+xml']);
    }
}
