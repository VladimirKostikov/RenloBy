<?php

declare(strict_types=1);

namespace App\Dto\Auth;

use App\Entity\User;

readonly class UserResponse
{
    public function __construct(
        public int $id,
        public string $email,
        public string $name,
        public array $roles,
        public bool $isTest,
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

    public static function fromEntity(User $user): self
    {
        return new self(
            $user->getId() ?? 0,
            $user->getEmail(),
            $user->getName(),
            $user->getRoles(),
            $user->isTest(),
            $user->getLastName(),
            $user->getFirstName(),
            $user->getPatronymic(),
            $user->getPhoto(),
            $user->getPhone(),
            $user->getInstagram(),
            $user->getTelegram(),
            $user->getWhatsapp(),
            $user->getViber(),
        );
    }
}
