<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Rfc;
use App\Form\RfcType;
use Doctrine\ORM\EntityManagerInterface;
use Gyro\MVC\FormRequest;
use Gyro\MVC\RedirectRoute;
use Laminas\Feed\Writer\Feed;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    #[Route('/', name: 'homepage')]
    public function indexAction(Request $request): array
    {
        $githubUserId = $request->getSession()->get('github_user_id');

        $rfcRepository = $this->entityManager->getRepository(Rfc::CLASS);

        $rfcs = array_reverse($rfcRepository->findBy([], ['targetPhpVersion' => 'ASC']));

        $data = [];
        foreach ($rfcs as $rfc) {
            assert($rfc instanceof Rfc);

            $data[] = $this->convertRfcToViewModel($rfc);
        }

        $activeFilter = static fn ($item) => $item['status'] === 'open' && ! $item['rejected'];
        $othersFilter = static fn ($item) => $item['status'] !== 'open' && ! $item['rejected'];

        $result = ['logged_in' => $request->getSession()->has('github_user_id')];
        $result['rejectedRfcs'] = array_values(array_filter($data, static fn ($item) => $item['rejected']));
        $result['activeRfcs'] = array_values(array_filter($data, $activeFilter));
        $others = array_values(array_filter($data, $othersFilter));

        $result['otherRfcs'] = ['unknown' => []];

        foreach ($others as $other) {
            if (! isset($result['otherRfcs'][$other['targetPhpVersion']])) {
                $result['otherRfcs'][$other['targetPhpVersion']] = [];
            }

            $result['otherRfcs'][$other['targetPhpVersion']][] = $other;
        }

        return $result;
    }

    /**
     * @return array<string,mixed>
     */
    #[Route('/rfc/{slug}', name: 'view')]
    public function viewAction(string $slug, Request $request): array
    {
        $githubUserId = $request->getSession()->get('github_user_id');

        $rfcRepository = $this->entityManager->getRepository(Rfc::class);

        $rfc = $rfcRepository->findOneBy(['url' => 'https://wiki.php.net/rfc/' . $slug]);

        if (! $rfc) {
            throw new NotFoundHttpException();
        }

        return [
            'rfc' => $this->convertRfcToViewModel($rfc),
            'logged_in' => $githubUserId !== null,
        ];
    }

    /** @return array<string, mixed> */
    private function convertRfcToViewModel(Rfc $rfc): array
    {
        return [
            'id' => $rfc->id,
            'title' => $rfc->title,
            'url' => $rfc->url,
            'slug' => $rfc->getSlug(),
            'status' => $rfc->status,
            'targetPhpVersion' => $rfc->targetPhpVersion,
            'discussions' => $rfc->discussions,
            'questions' => $rfc->tallyQuestionResults(),
            'rejected' => $rfc->rejected,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    #[Route('/admin', name: 'admin')]
    public function adminAction(): array
    {
        $rfcRepository = $this->entityManager->getRepository(Rfc::CLASS);
        $rfcs = array_reverse($rfcRepository->findAll());

        return ['rfcs' => $rfcs];
    }

    /**
     * @return array<string,mixed>|RedirectRoute
     */
    #[Route('/admin/rfc/{id}', name: 'admin_edit_rfc', methods: ['POST', 'GET'])]
    public function adminEditRfcAction(Rfc $rfc, FormRequest $request): array|RedirectRoute
    {
        if (! $request->handle(RfcType::class, $rfc)) {
            return ['rfc' => $rfc, 'form' => $request->createFormView()];
        }

        $this->entityManager->flush();

        return new RedirectRoute('admin');
    }

    #[Route('/admin/rfc/{id}/delete', name: 'admin_delete_rfc', methods: ['POST'])]
    public function adminDeleteRfcAction(Rfc $rfc): RedirectRoute
    {
        $this->entityManager->remove($rfc);
        $this->entityManager->flush();

        return new RedirectRoute('admin');
    }

    /**
     * @return array<string,mixed>
     */
    #[Route('/admin/rfc/{id}/export', name: 'admin_export_rfc', methods: ['GET'])]
    public function adminExportRfcAction(Rfc $rfc): array
    {
        return ['rfc' => $rfc];
    }

    /**
     * @return array<string, mixed>
     */
    #[Route('/notify', name: 'notify')]
    public function newsletterAction(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    #[Route('/optin', name: 'optin')]
    public function optinAction(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    #[Route('/confirm', name: 'confirm')]
    public function confirmAction(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    #[Route('/unsubscribe', name: 'unsubscribe')]
    public function unsubscribeAction(): array
    {
        return [];
    }

    #[Route('/atom.xml', name: 'atom')]
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

            if (! $modifiedDateSet) {
                $feed->setDateModified((int) $rfc->created->format('U'));
                $modifiedDateSet = true;
            }

            $content = $rfc->asFeedText();

            $entry = $feed->createEntry();
            $entry->setTitle($rfc->title);
            $entry->setLink($rfc->url);
            $entry->setDateModified((int) $rfc->created->format('U'));
            $entry->setDateCreated((int) $rfc->created->format('U'));
            $entry->setDescription($content);
            $entry->setContent($content);

            $feed->addEntry($entry);
        }

        if (! $modifiedDateSet) {
            $feed->setDateModified(time());
        }

        return new Response($feed->export('atom'), 200, ['Content-Type' => 'application/atom+xml']);
    }
}
