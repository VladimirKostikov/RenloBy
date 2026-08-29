<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\Collection\CollectionOwner;
use App\Entity\Favorite;
use App\Entity\Listing;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorite::class);
    }

    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['id' => 'DESC']);
    }

    public function findByGuestSession(string $guestSessionHash): array
    {
        return $this->findBy(['guestSessionHash' => $guestSessionHash], ['id' => 'DESC']);
    }

    public function findByOwner(CollectionOwner $owner): array
    {
        if ($owner->user instanceof User) {
            return $this->findByUser($owner->user);
        }

        return $this->findByGuestSession((string) $owner->guestSessionHash);
    }

    public function findOneByUserAndListing(User $user, Listing $listing): ?Favorite
    {
        return $this->findOneBy(['user' => $user, 'listing' => $listing]);
    }

    public function findOneByGuestSessionAndListing(string $guestSessionHash, Listing $listing): ?Favorite
    {
        return $this->findOneBy(['guestSessionHash' => $guestSessionHash, 'listing' => $listing]);
    }

    public function findOneByOwnerAndListing(CollectionOwner $owner, Listing $listing): ?Favorite
    {
        if ($owner->user instanceof User) {
            return $this->findOneByUserAndListing($owner->user, $listing);
        }

        return $this->findOneByGuestSessionAndListing((string) $owner->guestSessionHash, $listing);
    }

    public function findOneByOwnerAndListingIncludingDeleted(CollectionOwner $owner, Listing $listing): ?Favorite
    {
        return $this->withSoftDeletedVisible(
            fn (): ?Favorite => $this->findOneByOwnerAndListing($owner, $listing),
        );
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    private function withSoftDeletedVisible(callable $callback): mixed
    {
        $filters = $this->getEntityManager()->getFilters();
        $wasEnabled = $filters->isEnabled('soft_delete');
        if ($wasEnabled) {
            $filters->disable('soft_delete');
        }

        try {
            return $callback();
        } finally {
            if ($wasEnabled && !$filters->isEnabled('soft_delete')) {
                $filters->enable('soft_delete');
            }
        }
    }
}
