<?php

declare(strict_types=1);

namespace App\Dto\User;

readonly class UpdateUserRequest
{
    public function __construct(
        public ?string $email = null,
        public ?string $password = null,
        public ?array $roles = null,
        public ?bool $isTest = null,
        public ?string $lastName = null,
        public ?string $firstName = null,
        public ?string $patronymic = null,
        public bool $updateNameParts = false,
        public ?string $photo = null,
        public ?string $phone = null,
        public ?string $instagram = null,
        public ?string $telegram = null,
        public ?string $whatsapp = null,
        public ?string $viber = null,
        public bool $clearPhoto = false,
    ) {
    }
}
