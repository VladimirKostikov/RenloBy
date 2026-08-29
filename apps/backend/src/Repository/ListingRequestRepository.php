<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ListingRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ListingRequest>
 */
class ListingRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ListingRequest::class);
    }

    /**
     * @return list<ListingRequest>
     */
    public function findByListingOwner(User $user): array
    {
        /** @var list<ListingRequest> $requests */
        $requests = $this->createQueryBuilder('r')
            ->innerJoin('r.listing', 'l')
            ->andWhere('l.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $requests;
    }
}
