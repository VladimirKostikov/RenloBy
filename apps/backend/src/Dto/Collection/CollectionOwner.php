<?php

declare(strict_types=1);

namespace App\Dto\Collection;

use App\Entity\User;

readonly class CollectionOwner
{
    public function __construct(
        public ?User $user,
        public ?string $guestSessionHash,
    ) {
        if ($this->user === null && ($this->guestSessionHash === null || $this->guestSessionHash === '')) {
            throw new \InvalidArgumentException('Collection owner is required');
        }

        if ($this->user !== null && $this->guestSessionHash !== null) {
            throw new \InvalidArgumentException('Collection owner must be either user or guest session');
        }
    }

    public function isGuest(): bool
    {
        return $this->user === null;
    }
}
