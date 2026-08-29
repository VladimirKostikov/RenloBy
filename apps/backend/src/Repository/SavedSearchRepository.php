<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SavedSearch;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SavedSearchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SavedSearch::class);
    }

    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['id' => 'DESC']);
    }
}
