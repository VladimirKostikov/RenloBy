<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\City;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, City::class);
    }

    public function findOneBySlug(string $slug): ?City
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function findOneByNameIgnoreCase(string $name): ?City
    {
        return $this->createQueryBuilder('c')
            ->andWhere('LOWER(c.name) = LOWER(:name)')
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
