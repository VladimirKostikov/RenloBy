<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\City;
use App\Entity\District;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DistrictRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, District::class);
    }

    public function findByCity(City $city): array
    {
        return $this->findBy(['city' => $city], ['name' => 'ASC']);
    }

    public function findOneByCityAndNameIgnoreCase(City $city, string $name): ?District
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.city = :city')
            ->andWhere('LOWER(d.name) = LOWER(:name)')
            ->setParameter('city', $city)
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
