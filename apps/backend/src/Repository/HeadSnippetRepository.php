<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\HeadSnippet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HeadSnippet>
 */
class HeadSnippetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HeadSnippet::class);
    }

    /**
     * @return list<HeadSnippet>
     */
    public function findAllOrdered(): array
    {
        return $this->findBy([], ['sortOrder' => 'ASC', 'id' => 'ASC']);
    }

    /**
     * @return list<HeadSnippet>
     */
    public function findEnabledOrdered(): array
    {
        return $this->findBy(['isEnabled' => true], ['sortOrder' => 'ASC', 'id' => 'ASC']);
    }
}
