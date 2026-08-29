<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ListingReport;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ListingReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ListingReport::class);
    }

    /**
     * @return list<ListingReport>
     */
    public function findByListingOwner(User $user): array
    {
        /** @var list<ListingReport> $reports */
        $reports = $this->createQueryBuilder('r')
            ->innerJoin('r.listing', 'l')
            ->andWhere('l.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $reports;
    }
}
