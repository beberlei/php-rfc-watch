<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\MercurePublisher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PingMercureCommand extends Command
{
    private $publisher;

    public function __construct(MercurePublisher $publisher)
    {
        $this->publisher = $publisher;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('php-rfc-watch:ping-mercure')
            ->setDescription('Ping mercure to force update of all clients');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->publisher->publish('ping', []);
    }
}
