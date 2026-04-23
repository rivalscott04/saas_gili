<?php

namespace App\Exceptions;

use Exception;

class PolicyException extends Exception
{
    public function __construct(
        private readonly string $reasonCode,
        string $message,
        private readonly int $statusCode = 403,
    ) {
        parent::__construct($message, $statusCode);
    }

    public static function forbidden(string $reasonCode, string $message): self
    {
        return new self($reasonCode, $message, 403);
    }

    public static function conflict(string $reasonCode, string $message): self
    {
        return new self($reasonCode, $message, 409);
    }

    public function reasonCode(): string
    {
        return $this->reasonCode;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
