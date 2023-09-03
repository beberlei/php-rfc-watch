<?php

declare(strict_types=1);

namespace App\Model;

class RunCommandMessage
{
    public function __construct(
        public readonly string $commandName,
    ) {
    }
}
