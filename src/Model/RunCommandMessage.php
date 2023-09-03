<?php

namespace App\Model;

class RunCommandMessage
{
    public function __construct(
        public readonly string $commandName,
    ) {
    }
}
