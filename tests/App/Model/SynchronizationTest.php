<?php

namespace App\Model;

use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\RequestForComment;

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

    public function testSynchronizeRfc()
    {
        $this->whenRfcFetcherUrlThenReturnHtmlDom('https://wiki.php.net/rfc/arrow_functions_v2', 'arrow_functions.html');

        $rfcs = $this->service->synchronizeRfcs(['https://wiki.php.net/rfc/arrow_functions_v2']);

        \Phake::verify($this->entityManager)->persist($this->isInstanceOf(RequestForComment::class));

        $rfc = current($rfcs);

        $this->assertEquals('https://wiki.php.net/rfc/arrow_functions_v2', $rfc->getUrl());
        $this->assertEquals('Arrow Functions 2.0', $rfc->getTitle());
        $this->assertEquals('open', $rfc->getStatus());
        $this->assertEquals('doodle__form__add_arrow_functions_as_described_in_php_7.4', $rfc->getVoteId());
        $this->assertEquals('Add arrow functions as described in PHP 7.4?', $rfc->getQuestion());
        $this->assertEquals([
            ['votes' => 37, 'share' => 37/44, 'option' => 'Yes'],
            ['votes' => 7, 'share' => 7/44, 'option' => 'No']
        ], $rfc->getCurrentResults());
    }

    public function whenRfcFetcherUrlThenReturnHtmlDom($url, $htmlFile)
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML(file_get_contents(__DIR__ . '/_fixtures/' . $htmlFile));
        \Phake::when($this->rfcFetcher)->getRfcDom($url)->thenReturn($dom);
    }
}
