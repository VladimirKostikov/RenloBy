<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\Collection\CollectionOwner;
use App\Entity\AiPreference;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AiPreference>
 */
class AiPreferenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AiPreference::class);
    }

    public function findLatestByOwner(CollectionOwner $owner): ?AiPreference
    {
        if ($owner->user instanceof User) {
            return $this->findOneBy(['user' => $owner->user], ['id' => 'DESC']);
        }

        return $this->findOneBy(
            ['guestSessionHash' => (string) $owner->guestSessionHash],
            ['id' => 'DESC'],
        );
    }

    /**
     * @return list<AiPreference>
     */
    public function findByOwner(CollectionOwner $owner): array
    {
        if ($owner->user instanceof User) {
            return $this->findBy(['user' => $owner->user], ['id' => 'DESC']);
        }

        return $this->findBy(
            ['guestSessionHash' => (string) $owner->guestSessionHash],
            ['id' => 'DESC'],
        );
    }

    /**
     * @return list<AiPreference>
     */
    public function findByGuestSession(string $guestSessionHash): array
    {
        return $this->findBy(['guestSessionHash' => $guestSessionHash], ['id' => 'DESC']);
    }
}
