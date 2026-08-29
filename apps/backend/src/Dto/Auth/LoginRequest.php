<?php

declare(strict_types=1);

namespace App\Dto\Auth;

readonly class LoginRequest
{
    public function __construct(
        public string $email,
        public string $password,
    ) {
    }
}
