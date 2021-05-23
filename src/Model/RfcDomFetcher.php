<?php

declare(strict_types=1);

namespace App\Model;

use Buzz\Browser;
use DOMDocument;

class RfcDomFetcher
{
    private Browser $browser;

    public function __construct(Browser $browser)
    {
        $this->browser = $browser;
    }

    public function getRfcDom(string $url): DOMDocument
    {
        $response = $this->browser->get($url);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('could not fetch RFC from url ' . $url);
        }

        return $response->toDomDocument();
    }
}
