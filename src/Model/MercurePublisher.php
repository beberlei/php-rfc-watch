<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;

class MercurePublisher
{
    private $publisher;

    public function __construct(PublisherInterface $publisher)
    {
        $this->publisher = $publisher;
    }

    public function publish(string $topic, array $data): void
    {
        try {
            ($this->publisher)(new Update($topic, json_encode($data)));
        } catch (TransportException | ClientException $e) {
            // ignore failures, they are not critical, but log for APM
            if (class_exists('Tideways\Profiler')) {
                \Tideways\Profiler::logException($e);
            }
        }
    }
}
