<?php

declare(strict_types=1);

namespace App\Model;

use DOMDocument;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RfcDomFetcher
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function getRfcDom(string $url): DOMDocument
    {
        $response = $this->httpClient->request('GET', $url);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('could not fetch RFC from url ' . $url);
        }

        $content = $response->getContent();
        $dom = new DOMDocument();
        @$dom->loadHTML($content);
        return $dom;
    }
}
