<?php

namespace App\Model;

use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManagerInterface;

class SynchronizationTest extends TestCase
{
    private $entityManager;
    private $rfcFetcher;
    private $service;

    public function setUp()
    {
        $this->service = new Synchronization(
            $this->entityManager = \Phake::mock(EntityManagerInterface::class),
            $this->rfcFetcher = \Phake::mock(RfcDomFetcher::class)
        );
    }

    public function testGetRfcUrlsInVoting()
    {
        $this->whenRfcFetcherUrlThenReturnHtmlDom('https://wiki.php.net/rfc', 'rfc_list.html');
        $rfcs = $this->service->getRfcUrlsInVoting();

        $this->assertEquals([
            "https://wiki.php.net/rfc/arrow_functions_v2",
            "https://wiki.php.net/rfc/deprecate_php_short_tags",
            "https://wiki.php.net/rfc/deprecate-and-remove-ext-interbase",
            "https://wiki.php.net/rfc/spread_operator_for_array",
            ], $rfcs);
    }

    public function whenRfcFetcherUrlThenReturnHtmlDom($url, $htmlFile)
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML(file_get_contents(__DIR__ . '/_fixtures/' . $htmlFile));
        \Phake::when($this->rfcFetcher)->getRfcDom($url)->thenReturn($dom);
    }
}
