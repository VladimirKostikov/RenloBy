<?php

declare(strict_types=1);

namespace App\Exception;

final class TooManyRequestsException extends \RuntimeException
{
    public function __construct(
        string $message = 'auth.rate_limited',
        private readonly int $retryAfterSeconds = 60,
    ) {
        parent::__construct($message);
    }

    public function getRetryAfterSeconds(): int
    {
        return $this->retryAfterSeconds;
    }
}
