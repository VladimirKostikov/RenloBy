<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\Listing\ListingSearchRequest;
use App\Entity\Listing;
use App\Entity\User;
use App\Enum\DealType;
use App\Enum\ListingStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ListingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Listing::class);
    }

    public function search(ListingSearchRequest $filters): array
    {
        $qb = $this->createQueryBuilder('l')
            ->leftJoin('l.city', 'city')
            ->leftJoin('l.district', 'district')
            ->leftJoin('l.metroStation', 'metro');

        if (!$filters->includeNonPublished && $filters->userId === null) {
            $qb->andWhere('l.status = :publishedStatus')
                ->setParameter('publishedStatus', ListingStatus::Published);
        } elseif ($filters->status !== null) {
            $qb->andWhere('l.status = :status')
                ->setParameter('status', $filters->status);
        } elseif (!$filters->includeNonPublished) {
            $qb->andWhere('l.status = :publishedStatus')
                ->setParameter('publishedStatus', ListingStatus::Published);
        }

        if ($filters->dealType !== null) {
            $qb->andWhere('l.dealType = :dealType')
                ->setParameter('dealType', $filters->dealType);
        }

        if ($filters->listingType !== null) {
            $qb->andWhere('l.listingType = :listingType')
                ->setParameter('listingType', $filters->listingType);
        }

        if ($filters->cityId !== null) {
            $qb->andWhere('city.id = :cityId')
                ->setParameter('cityId', $filters->cityId);
        } elseif ($filters->regionSlug !== null && $filters->regionSlug !== '') {
            $qb->andWhere('city.regionSlug = :regionSlug')
                ->setParameter('regionSlug', $filters->regionSlug);
        }

        if ($filters->districtId !== null) {
            $qb->andWhere('district.id = :districtId')
                ->setParameter('districtId', $filters->districtId);
        }

        if ($filters->rooms !== null) {
            $qb->andWhere('l.rooms = :rooms')
                ->setParameter('rooms', $filters->rooms);
        }

        if ($filters->floor !== null) {
            $qb->andWhere('l.floor = :floor')
                ->setParameter('floor', $filters->floor);
        }

        if ($filters->minArea !== null) {
            $qb->andWhere('l.area >= :minArea')
                ->setParameter('minArea', $filters->minArea);
        }

        if ($filters->maxArea !== null) {
            $qb->andWhere('l.area <= :maxArea')
                ->setParameter('maxArea', $filters->maxArea);
        }

        if ($filters->minPrice !== null) {
            $qb->andWhere('l.price >= :minPrice')
                ->setParameter('minPrice', $filters->minPrice);
        }

        if ($filters->maxPrice !== null) {
            $qb->andWhere('l.price <= :maxPrice')
                ->setParameter('maxPrice', $filters->maxPrice);
        }

        if ($filters->verified !== null) {
            $qb->andWhere('l.verified = :verified')
                ->setParameter('verified', $filters->verified);
        }

        if ($filters->rentTerm !== null) {
            $qb->andWhere('l.rentTerm = :rentTerm')
                ->setParameter('rentTerm', $filters->rentTerm);
        }

        if ($filters->hasDeposit === true) {
            $qb->andWhere('l.hasDeposit = true');
        }

        if ($filters->utilitiesIncluded === true) {
            $qb->andWhere('l.utilitiesIncluded = true');
        }

        if ($filters->noCommission === true) {
            $qb->andWhere('l.noCommission = true');
        }

        if ($filters->fromOwner === true) {
            $qb->andWhere('l.fromOwner = true');
        }

        if ($filters->hasRenovation === true) {
            $qb->andWhere('l.hasRenovation = true');
        }

        if ($filters->query !== null && trim($filters->query) !== '') {
            $term = '%' . addcslashes(mb_strtolower(trim($filters->query)), '%_') . '%';
            $qb->andWhere(
                $qb->expr()->orX(
                    'LOWER(l.address) LIKE :query',
                    'LOWER(city.name) LIKE :query',
                    'LOWER(district.name) LIKE :query',
                    'LOWER(metro.name) LIKE :query',
                ),
            )->setParameter('query', $term);
        }

        if ($filters->userId !== null) {
            $qb->andWhere('l.user = :userId')
                ->setParameter('userId', $filters->userId);
        }

        if ($filters->sort === 'random') {
            $qb->orderBy('RANDOM()', 'ASC');
        } else {
            $sortField = match ($filters->sort) {
                'price' => 'l.price',
                'area' => 'l.area',
                'views' => 'l.views',
                default => 'l.publishedAt',
            };

            $direction = strtoupper($filters->direction) === 'ASC' ? 'ASC' : 'DESC';
            $qb->orderBy($sortField, $direction);
        }

        $countQb = clone $qb;
        $total = (int) $countQb
            ->resetDQLPart('orderBy')
            ->select('COUNT(l.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $page = max(1, $filters->page);
        $limit = max(1, min(100, $filters->limit));
        $offset = ($page - 1) * $limit;

        $items = $qb->select('l')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    /**
     * @return list<array{
     *   address: string,
     *   cityId: int|null,
     *   cityName: string|null,
     *   districtId: int|null,
     *   districtName: string|null,
     *   metroStationId: int|null,
     *   metroName: string|null
     * }>
     */
    public function findAddressSuggestCandidates(string $needle, int $limit = 80): array
    {
        $term = '%' . addcslashes(mb_strtolower(trim($needle)), '%_') . '%';
        $limit = max(1, min(200, $limit));

        $qb = $this->createQueryBuilder('l')
            ->select(
                'l.address AS address',
                'IDENTITY(l.city) AS cityId',
                'city.name AS cityName',
                'IDENTITY(l.district) AS districtId',
                'district.name AS districtName',
                'IDENTITY(l.metroStation) AS metroStationId',
                'metro.name AS metroName',
            )
            ->leftJoin('l.city', 'city')
            ->leftJoin('l.district', 'district')
            ->leftJoin('l.metroStation', 'metro')
            ->andWhere('l.status = :publishedStatus')
            ->setParameter('publishedStatus', ListingStatus::Published)
            ->setParameter('term', $term)
            ->setMaxResults($limit);

        $qb->andWhere(
            $qb->expr()->orX(
                'LOWER(l.address) LIKE :term',
                'LOWER(city.name) LIKE :term',
                'LOWER(district.name) LIKE :term',
                'LOWER(metro.name) LIKE :term',
            ),
        );

        $rows = $qb->getQuery()->getArrayResult();

        return array_map(
            static fn (array $row): array => [
                'address' => (string) ($row['address'] ?? ''),
                'cityId' => isset($row['cityId']) ? (int) $row['cityId'] : null,
                'cityName' => isset($row['cityName']) ? (string) $row['cityName'] : null,
                'districtId' => isset($row['districtId']) ? (int) $row['districtId'] : null,
                'districtName' => isset($row['districtName']) ? (string) $row['districtName'] : null,
                'metroStationId' => isset($row['metroStationId']) ? (int) $row['metroStationId'] : null,
                'metroName' => isset($row['metroName']) ? (string) $row['metroName'] : null,
            ],
            $rows,
        );
    }

    public function countByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByUserAndStatus(User $user, ListingStatus $status): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.user = :user')
            ->andWhere('l.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<Listing>
     */
    public function findByUserOrderedByViews(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.user = :user')
            ->setParameter('user', $user)
            ->orderBy('l.views', 'DESC')
            ->setMaxResults(max(1, min(20, $limit)))
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{items: list<Listing>, total: int}
     */
    public function findByUserForAnalytics(
        User $user,
        int $page = 1,
        int $limit = 20,
        string $q = '',
    ): array {
        $page = max(1, $page);
        $limit = max(1, min(50, $limit));

        $qb = $this->createQueryBuilder('l')
            ->andWhere('l.user = :user')
            ->setParameter('user', $user);

        $query = trim($q);
        if ($query !== '') {
            if (ctype_digit($query)) {
                $qb->andWhere('(LOWER(l.address) LIKE :q OR l.id = :listingId)')
                    ->setParameter('q', '%' . mb_strtolower($query) . '%')
                    ->setParameter('listingId', (int) $query);
            } else {
                $qb->andWhere('LOWER(l.address) LIKE :q')
                    ->setParameter('q', '%' . mb_strtolower($query) . '%');
            }
        }

        $total = (int) (clone $qb)
            ->select('COUNT(l.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $qb
            ->orderBy('l.publishedAt', 'DESC')
            ->addOrderBy('l.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * @return list<Listing>
     */
    public function findSimilarNearby(Listing $listing, int $limit = 40): array
    {
        return $this->findMarketComps($listing, $limit);
    }

    /**
     * Published comps in the same city/deal/type with nearby room count.
     *
     * @return list<Listing>
     */
    public function findMarketComps(Listing $listing, int $limit = 80): array
    {
        $qb = $this->createQueryBuilder('l')
            ->andWhere('l.status = :status')
            ->andWhere('l.dealType = :dealType')
            ->andWhere('l.listingType = :listingType')
            ->andWhere('l.city = :city')
            ->andWhere('l.rooms BETWEEN :roomsMin AND :roomsMax')
            ->setParameter('status', ListingStatus::Published)
            ->setParameter('dealType', $listing->getDealType())
            ->setParameter('listingType', $listing->getListingType())
            ->setParameter('city', $listing->getCity())
            ->setParameter('roomsMin', max(0, $listing->getRooms() - 1))
            ->setParameter('roomsMax', $listing->getRooms() + 1)
            ->orderBy('l.publishedAt', 'DESC')
            ->setMaxResults(max(1, min(120, $limit)));

        if ($listing->getId() !== null) {
            $qb->andWhere('l.id != :id')->setParameter('id', $listing->getId());
        }

        $district = $listing->getDistrict();
        if ($district !== null && $district->getId() !== null) {
            $qb->addSelect('CASE WHEN l.district = :district THEN 0 ELSE 1 END AS HIDDEN districtRank')
                ->setParameter('district', $district)
                ->orderBy('districtRank', 'ASC')
                ->addOrderBy('l.publishedAt', 'DESC');
        }

        /** @var list<Listing> $rows */
        $rows = $qb->getQuery()->getResult();

        return $rows;
    }

    /**
     * @return list<array{dealType: string, count: string}>
     */
    public function countGroupedByDealType(User $user): array
    {
        return $this->createQueryBuilder('l')
            ->select('l.dealType AS dealType')
            ->addSelect('COUNT(l.id) AS count')
            ->andWhere('l.user = :user')
            ->setParameter('user', $user)
            ->groupBy('l.dealType')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @return list<array{status: string, count: string}>
     */
    public function countGroupedByStatus(User $user): array
    {
        return $this->createQueryBuilder('l')
            ->select('l.status AS status')
            ->addSelect('COUNT(l.id) AS count')
            ->andWhere('l.user = :user')
            ->setParameter('user', $user)
            ->groupBy('l.status')
            ->getQuery()
            ->getArrayResult();
    }

    public function sumViewsByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COALESCE(SUM(l.views), 0)')
            ->andWhere('l.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function statsByCity(?DealType $dealType): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('IDENTITY(l.city) AS id')
            ->addSelect('COUNT(l.id) AS count')
            ->addSelect('AVG(l.price) AS avgPrice')
            ->addSelect('AVG(l.pricePerSqm) AS avgPricePerSqm')
            ->andWhere('l.status = :publishedStatus')
            ->setParameter('publishedStatus', ListingStatus::Published)
            ->groupBy('l.city');

        if ($dealType !== null) {
            $qb->andWhere('l.dealType = :dealType')
                ->setParameter('dealType', $dealType);
        }

        return $qb->getQuery()->getArrayResult();
    }

    public function statsByDistrict(?DealType $dealType): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('IDENTITY(l.district) AS id')
            ->addSelect('COUNT(l.id) AS count')
            ->addSelect('AVG(l.price) AS avgPrice')
            ->addSelect('AVG(l.pricePerSqm) AS avgPricePerSqm')
            ->andWhere('l.status = :publishedStatus')
            ->setParameter('publishedStatus', ListingStatus::Published)
            ->groupBy('l.district');

        if ($dealType !== null) {
            $qb->andWhere('l.dealType = :dealType')
                ->setParameter('dealType', $dealType);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @return list<array{id: int, latitude: float, longitude: float, address: string|null}>
     */
    public function findCoordinatesInBbox(
        float $south,
        float $west,
        float $north,
        float $east,
        int $limit = 300,
    ): array {
        $rows = $this->createQueryBuilder('l')
            ->select('l.id AS id', 'l.latitude AS latitude', 'l.longitude AS longitude', 'l.address AS address')
            ->andWhere('l.status = :publishedStatus')
            ->andWhere('l.latitude >= :south')
            ->andWhere('l.latitude <= :north')
            ->andWhere('l.longitude >= :west')
            ->andWhere('l.longitude <= :east')
            ->setParameter('publishedStatus', ListingStatus::Published)
            ->setParameter('south', $south)
            ->setParameter('north', $north)
            ->setParameter('west', $west)
            ->setParameter('east', $east)
            ->setMaxResults(max(1, min(500, $limit)))
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'latitude' => (float) $row['latitude'],
                'longitude' => (float) $row['longitude'],
                'address' => isset($row['address']) ? (string) $row['address'] : null,
            ],
            $rows,
        );
    }
}
