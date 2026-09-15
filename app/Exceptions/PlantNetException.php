<?php

namespace App\Exceptions;

use RuntimeException;

class PlantNetException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $status = 502,
    ) {
        parent::__construct($message, $status);
    }

    public function status(): int
    {
        return $this->status;
    }
}
