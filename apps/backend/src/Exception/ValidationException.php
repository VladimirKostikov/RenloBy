<?php

declare(strict_types=1);

namespace App\Exception;

class ValidationException extends \RuntimeException
{
    public function __construct(
        public readonly array $fields,
        string $message = 'validation.failed',
    ) {
        parent::__construct($message);
    }
}
