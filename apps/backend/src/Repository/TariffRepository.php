<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tariff;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tariff>
 */
class TariffRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tariff::class);
    }

    /**
     * @return list<Tariff>
     */
    public function findAllOrdered(): array
    {
        return $this->findBy([], ['sortOrder' => 'ASC', 'id' => 'ASC']);
    }

    public function findOneByCode(string $code, bool $isTest = false): ?Tariff
    {
        return $this->findOneBy(['code' => $code, 'isTest' => $isTest]);
    }
}
