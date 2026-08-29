<?php

declare(strict_types=1);

namespace App\Dto\Seller;

use App\Entity\User;

readonly class SellerProfileResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $photo,
        public ?string $phone,
        public ?string $instagram,
        public ?string $telegram,
        public ?string $whatsapp,
        public ?string $viber,
        public ?string $lastSeenAt,
        public string $registeredAt,
        public int $listingsCount,
    ) {
    }

    public static function fromEntity(User $user, int $listingsCount): self
    {
        return new self(
            $user->getId() ?? 0,
            $user->getName(),
            $user->getPhoto(),
            $user->getPhone(),
            $user->getInstagram(),
            $user->getTelegram(),
            $user->getWhatsapp(),
            $user->getViber(),
            $user->getLastSeenAt()?->format(\DateTimeInterface::ATOM),
            $user->getRegisteredAt()->format(\DateTimeInterface::ATOM),
            $listingsCount,
        );
    }
}
