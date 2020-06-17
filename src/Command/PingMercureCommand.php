<?php

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

    protected function configure()
    {
        $this
            ->setName('php-rfc-watch:ping-mercure')
            ->setDescription('Ping mercure to force update of all clients')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ($this->publisher)->publish('ping', []);
    }
}