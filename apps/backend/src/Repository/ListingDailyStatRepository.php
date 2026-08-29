<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Listing;
use App\Entity\ListingDailyStat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ListingDailyStat>
 */
class ListingDailyStatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ListingDailyStat::class);
    }

    public function findOneByListingAndDay(Listing $listing, \DateTimeImmutable $day): ?ListingDailyStat
    {
        return $this->findOneBy([
            'listing' => $listing,
            'day' => $day,
        ]);
    }

    /**
     * @return list<ListingDailyStat>
     */
    public function findForListingSince(Listing $listing, \DateTimeImmutable $from): array
    {
        /** @var list<ListingDailyStat> $rows */
        $rows = $this->createQueryBuilder('s')
            ->andWhere('s.listing = :listing')
            ->andWhere('s.day >= :from')
            ->setParameter('listing', $listing)
            ->setParameter('from', $from)
            ->orderBy('s.day', 'ASC')
            ->getQuery()
            ->getResult();

        return $rows;
    }
}
