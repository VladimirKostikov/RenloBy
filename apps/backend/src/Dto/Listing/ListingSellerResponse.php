<?php

declare(strict_types=1);

namespace App\Dto\Listing;

use App\Entity\User;

readonly class ListingSellerResponse
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
    ) {
    }

    public static function fromEntity(User $user): self
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
        );
    }
}
