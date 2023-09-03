<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RunCommandHandler
{
    public function __construct(
        private KernelInterface $kernel,
    ) {
    }

    public function __invoke(RunCommandMessage $message): void
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        $application->run(new StringInput($message->commandName));
    }
}
