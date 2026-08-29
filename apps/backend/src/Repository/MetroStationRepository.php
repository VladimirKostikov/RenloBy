<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\City;
use App\Entity\MetroStation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MetroStationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MetroStation::class);
    }

    public function findByCity(City $city): array
    {
        return $this->findBy(['city' => $city], ['name' => 'ASC']);
    }

    public function findOneByCityAndNameIgnoreCase(City $city, string $name): ?MetroStation
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.city = :city')
            ->andWhere('LOWER(m.name) = LOWER(:name)')
            ->setParameter('city', $city)
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
