<?php

namespace App\Command;

use App\Model\Synchronization;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SynchronizeVotesCommand extends Command
{
    private $synchronization;

    public function __construct(Synchronization $synchronization)
    {
        $this->synchronization = $synchronization;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('php-rfc-watch:synchronize')
            ->setDescription('Synchronize the Current votes from wiki.php.net to RFC Watch')
            ->addArgument('urls', InputArgument::IS_ARRAY)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $urls = $input->getArgument('urls') ?: $this->synchronization->getRfcUrlsInVoting();

        $this->synchronization->synchronizeRfcs($urls);
    }
}
