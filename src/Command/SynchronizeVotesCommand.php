<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\Synchronization;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SynchronizeVotesCommand extends Command
{
    private Synchronization $synchronization;

    public function __construct(Synchronization $synchronization)
    {
        $this->synchronization = $synchronization;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('php-rfc-watch:synchronize')
            ->setDescription('Synchronize the Current votes from wiki.php.net to RFC Watch')
            ->addArgument('urls', InputArgument::IS_ARRAY)
            ->addOption('target', 't', InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $urls = $input->getArgument('urls') ?: $this->synchronization->getRfcUrlsInVoting();
        $targetPhpVersion = $input->getOption('target');

        $this->synchronization->synchronizeRfcs($urls, $targetPhpVersion);

        return 0;
    }
}
