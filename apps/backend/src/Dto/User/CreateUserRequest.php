<?php

declare(strict_types=1);

namespace App\Dto\User;

readonly class CreateUserRequest
{
    public function __construct(
        public string $email,
        public string $password,
        public array $roles = [],
        public ?bool $isTest = null,
        public ?string $lastName = null,
        public ?string $firstName = null,
        public ?string $patronymic = null,
        public ?string $photo = null,
        public ?string $phone = null,
        public ?string $instagram = null,
        public ?string $telegram = null,
        public ?string $whatsapp = null,
        public ?string $viber = null,
    ) {
    }
}
