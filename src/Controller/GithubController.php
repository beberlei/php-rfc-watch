<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GithubController
{
    private string $clientId;
    private string $clientSecret;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(string $clientId, string $clientSecret, UrlGeneratorInterface $urlGenerator)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @Route("/login", name="login")
     */
    public function authorizeAction(Request $request)
    {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->urlGenerator->generate('login_check', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'state' => base64_encode(random_bytes(64)),
        ];

        $request->getSession()->set('_github_state', $params['state']);

        return new RedirectResponse('https://github.com/login/oauth/authorize?' . http_build_query($params));
    }

    /**
     * @Route("/login_check", name="login_check")
     */
    public function checkAction(Request $request)
    {
        $session = $request->getSession();
        $state = $session->get('_github_state');

        if ($state !== $request->query->get('state')) {
            throw new AccessDeniedHttpException();
        }

        $browser = new \Buzz\Browser();
        $response = $browser->post(
            'https://github.com/login/oauth/access_token',
            ['Accept: application/json', 'User-Agent: https://php-rfc-watch.beberlei.com'],
            http_build_query([
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code' => $request->query->get('code'),
            ])
        );

        if ($response->getStatusCode() !== 200) {
            throw new AccessDeniedHttpException();
        }

        $token = json_decode($response->getContent(), true);

        $response = $browser->get(
            'https://api.github.com/user',
            ['Authorization: token ' . $token['access_token'], 'User-Agent: https://php-rfc-watch.beberlei.com']
        );

        if ($response->getStatusCode() !== 200) {
            throw new AccessDeniedHttpException();
        }

        $user = json_decode($response->getContent(), true);

        $session->set('github_user_id', $user['id']);
        $session->remove('_github_state');

        return new RedirectResponse($this->urlGenerator->generate('homepage'));
    }
}
