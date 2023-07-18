<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Rfc;
use App\Repository\RfcRepository;
use PHPUnit\Framework\TestCase;

class SynchronizationTest extends TestCase
{
    private RfcRepository $rfcRepository;
    private RfcDomFetcher $rfcFetcher;
    private Synchronization $service;

    public function setUp(): void
    {
        $this->service = new Synchronization(
            $this->rfcRepository = \Phake::mock(RfcRepository::class),
            $this->rfcFetcher = \Phake::mock(RfcDomFetcher::class)
        );
    }

    public function testGetRfcUrlsInVoting(): void
    {
        $this->whenRfcFetcherUrlThenReturnHtmlDom('https://wiki.php.net/rfc', 'rfc_list.html');
        $rfcs = $this->service->getRfcUrlsInVoting();

        $this->assertEquals([
            'https://wiki.php.net/rfc/arrow_functions_v2',
            'https://wiki.php.net/rfc/deprecate_php_short_tags',
            'https://wiki.php.net/rfc/deprecate-and-remove-ext-interbase',
            'https://wiki.php.net/rfc/spread_operator_for_array',
        ], $rfcs);
    }

    public function testSynchronizeRfcWithoutVotingPage(): void
    {
        $this->whenRfcFetcherUrlThenReturnHtmlDom(
            'https://wiki.php.net/rfc/arrow_functions_v2',
            'arrow_functions_no_vote.html'
        );

        \Phake::when($this->rfcRepository)->findOneByUrl('https://wiki.php.net/rfc/arrow_functions_v2')
            ->thenReturn(null);

        $rfcs = $this->service->synchronizeRfcs(['https://wiki.php.net/rfc/arrow_functions_v2']);

        \Phake::verify($this->rfcRepository, \Phake::times(0))->persist($this->isInstanceOf(Rfc::class));

        $rfc = current($rfcs);

        assert($rfc instanceof Rfc);
    }

    public function testSynchronizeRfc(): void
    {
        $this->whenRfcFetcherUrlThenReturnHtmlDom(
            'https://wiki.php.net/rfc/arrow_functions_v2',
            'arrow_functions.html'
        );

        \Phake::when($this->rfcRepository)->findOneByUrl('https://wiki.php.net/rfc/arrow_functions_v2')
            ->thenReturn(null);

        $rfcs = $this->service->synchronizeRfcs(['https://wiki.php.net/rfc/arrow_functions_v2']);

        \Phake::verify($this->rfcRepository)->persist($this->isInstanceOf(Rfc::class));

        $rfc = current($rfcs);

        assert($rfc instanceof Rfc);

        $this->assertEquals('https://wiki.php.net/rfc/arrow_functions_v2', $rfc->url);
        $this->assertEquals('Arrow Functions 2.0', $rfc->title);
        $this->assertEquals('open', $rfc->status);
        $this->assertEquals('7.4', $rfc->targetPhpVersion);

        $this->assertTrue(
            isset($rfc->votes['doodle__form__add_arrow_functions_as_described_in_php_7.4']),
            'Collection has key "doodle__form__add_arrow_functions_as_described_in_php_7.4"'
        );

        $vote = $rfc->votes['doodle__form__add_arrow_functions_as_described_in_php_7.4'];

        $this->assertEquals('Add arrow functions as described in PHP 7.4?', $vote->question);
        $this->assertEquals(['Yes' => 37, 'No' => 7], $vote->currentVotes);
    }

    public function whenRfcFetcherUrlThenReturnHtmlDom(string $url, string $htmlFile): void
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML(file_get_contents(__DIR__ . '/_fixtures/' . $htmlFile));
        \Phake::when($this->rfcFetcher)->getRfcDom($url)->thenReturn($dom);
    }
}
