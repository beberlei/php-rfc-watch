<?php

namespace App\Model;

use DOMDocument;
use Buzz\Browser;

class RfcDomFetcher
{
    private $browser;

    public function __construct(Browser $browser)
    {
        $this->browser = $browser;
    }

    public function getRfcDom($url) : DOMDocument
    {
        $response = $this->browser->get($url);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException("could not fetch RFC from url " . $url);
        }

        return $response->toDomDocument();
    }
}
