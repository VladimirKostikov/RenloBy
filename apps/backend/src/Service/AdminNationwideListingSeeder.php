<?php

declare(strict_types=1);

namespace App\Service;

use App\DataFixtures\AdminNationwideListingSeedDefinitions;
use App\Entity\City;
use App\Entity\District;
use App\Entity\Listing;
use App\Entity\MetroStation;
use App\Entity\User;
use App\Enum\DealType;
use App\Enum\ListingStatus;
use App\Enum\ListingType;
use App\Enum\RentTerm;
use App\Factory\ListingFactory;
use App\Repository\CityRepository;
use App\Repository\DistrictRepository;
use App\Repository\ListingRepository;
use App\Repository\MetroStationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

final class AdminNationwideListingSeeder
{
    /** @var array<string, array{0: float, 1: float}> */
    private const CITY_COORDS = [
        'minsk' => [53.9045, 27.5615],
        'borisov' => [54.2279, 28.5050],
        'soligorsk' => [52.7928, 27.5414],
        'molodechno' => [54.3107, 26.8512],
        'brest-city' => [52.0976, 23.7341],
        'vitebsk-city' => [55.1904, 30.2049],
        'gomel-city' => [52.4345, 30.9754],
        'grodno-city' => [53.6693, 23.8131],
        'mogilev-city' => [53.8945, 30.3307],
        'zhodino' => [53.3447, 28.3236],
        'berezino' => [53.8378, 27.6906],
        'mir' => [53.4514, 26.4729],
        'motol' => [52.3147, 25.5739],
        'chechersk' => [52.9164, 30.9179],
    ];

    public function __construct(
        private readonly ListingFactory $listingFactory,
        private readonly ListingImageCatalog $imageCatalog,
        private readonly UserRepository $userRepository,
        private readonly CityRepository $cityRepository,
        private readonly DistrictRepository $districtRepository,
        private readonly MetroStationRepository $metroStationRepository,
        private readonly ListingRepository $listingRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{created: int, skipped: int}
     */
    public function seed(bool $skipExisting = true): array
    {
        $admin = $this->userRepository->findOneByEmail(AdminNationwideListingSeedDefinitions::ADMIN_EMAIL);
        if ($admin === null) {
            throw new \RuntimeException('Admin user not found: ' . AdminNationwideListingSeedDefinitions::ADMIN_EMAIL);
        }

        $minsk = $this->cityRepository->findOneBySlug('minsk');
        $metroStations = $minsk !== null ? $this->metroStationRepository->findByCity($minsk) : [];
        $created = 0;
        $skipped = 0;

        foreach (AdminNationwideListingSeedDefinitions::all() as $index => $row) {
            $city = $this->cityRepository->findOneBySlug($row['citySlug']);
            if ($city === null) {
                throw new \RuntimeException('City not found: ' . $row['citySlug']);
            }

            $districts = $this->districtRepository->findByCity($city);
            if ($districts === []) {
                throw new \RuntimeException('No districts for city: ' . $row['citySlug']);
            }

            if ($skipExisting && $this->existsForAdmin($admin, $city, $row['address'])) {
                ++$skipped;
                continue;
            }

            $district = $districts[$index % count($districts)];
            $metro = null;
            if ($row['citySlug'] === 'minsk' && $metroStations !== []) {
                $metro = $metroStations[$index % count($metroStations)];
            }

            $created += $this->persistPair($admin, $city, $district, $metro, $row, $index);
        }

        $this->entityManager->flush();

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * @param list<MetroStation> $metroStations
     * @param array<string, City> $citiesBySlug
     * @param array<string, list<District>> $districtsByCity
     *
     * @return list<Listing>
     */
    public function seedIntoManager(
        ObjectManager $manager,
        User $admin,
        array $citiesBySlug,
        array $districtsByCity,
        array $metroStations,
    ): array {
        $created = [];

        foreach (AdminNationwideListingSeedDefinitions::all() as $index => $row) {
            $city = $citiesBySlug[$row['citySlug']] ?? null;
            $districts = $districtsByCity[$row['citySlug']] ?? [];
            if ($city === null || $districts === []) {
                continue;
            }

            $district = $districts[$index % count($districts)];
            $metro = $row['citySlug'] === 'minsk' && $metroStations !== []
                ? $metroStations[$index % count($metroStations)]
                : null;

            foreach ([true] as $isTest) {
                $listing = $this->buildListing($admin, $city, $district, $metro, $row, $index, $isTest);
                $manager->persist($listing);
                $created[] = $listing;
            }
        }

        return $created;
    }

    /**
     * @param array{
     *     citySlug: string,
     *     dealType: string,
     *     listingType: string,
     *     price: int,
     *     rooms: int,
     *     area: float,
     *     floor: int,
     *     totalFloors: int,
     *     address: string,
     *     rentTerm: string|null,
     *     verified: bool,
     *     fromOwner: bool,
     *     hasRenovation: bool,
     *     hasDeposit: bool,
     *     utilitiesIncluded: bool,
     *     noCommission: bool,
     *     latOffset: float,
     *     lngOffset: float
     * } $row
     */
    private function persistPair(
        User $admin,
        City $city,
        District $district,
        ?MetroStation $metro,
        array $row,
        int $index,
    ): int {
        $count = 0;
        foreach ([true] as $isTest) {
            $listing = $this->buildListing($admin, $city, $district, $metro, $row, $index, $isTest);
            $this->entityManager->persist($listing);
            ++$count;
        }

        return $count;
    }

    /**
     * @param array{
     *     citySlug: string,
     *     dealType: string,
     *     listingType: string,
     *     price: int,
     *     rooms: int,
     *     area: float,
     *     floor: int,
     *     totalFloors: int,
     *     address: string,
     *     rentTerm: string|null,
     *     verified: bool,
     *     fromOwner: bool,
     *     hasRenovation: bool,
     *     hasDeposit: bool,
     *     utilitiesIncluded: bool,
     *     noCommission: bool,
     *     latOffset: float,
     *     lngOffset: float
     * } $row
     */
    private function buildListing(
        User $admin,
        City $city,
        District $district,
        ?MetroStation $metro,
        array $row,
        int $index,
        bool $isTest,
    ): Listing {
        [$baseLat, $baseLng] = self::CITY_COORDS[$row['citySlug']] ?? [53.9045, 27.5615];
        $dealType = DealType::from($row['dealType']);
        $listingType = ListingType::from($row['listingType']);
        $price = $row['price'];
        $area = $row['area'];

        $listing = $this->listingFactory->create(
            $admin,
            $city,
            $district,
            $dealType,
            $listingType,
            $metro,
            $isTest,
        );

        $listing
            ->setStatus(ListingStatus::Published)
            ->setIsTest($isTest)
            ->setPrice($price)
            ->setPricePerSqm((int) round($price / $area))
            ->setRooms($row['rooms'])
            ->setArea($area)
            ->setFloor($row['floor'])
            ->setTotalFloors($row['totalFloors'])
            ->setAddress($row['address'])
            ->setLatitude($baseLat + $row['latOffset'])
            ->setLongitude($baseLng + $row['lngOffset'])
            ->setMetroMinutes($metro !== null ? ($index % 18) + 2 : null)
            ->setVerified($row['verified'])
            ->setFromOwner($row['fromOwner'])
            ->setHasRenovation($row['hasRenovation'])
            ->setHasDeposit($row['hasDeposit'])
            ->setUtilitiesIncluded($row['utilitiesIncluded'])
            ->setNoCommission($row['noCommission'])
            ->setAiGoodPrice($index % 5 === 0)
            ->setViews(20 + $index * 5)
            ->setContactOpens(max(1, (int) floor((20 + $index * 5) * 0.08)))
            ->setMessages(max(0, (int) floor((20 + $index * 5) * 0.04)))
            ->setImages($this->imageCatalog->forIndex($index + 200))
            ->setPublishedAt(new \DateTimeImmutable('-' . ($index + 1) . ' hours'));

        if ($dealType === DealType::Rent && is_string($row['rentTerm'])) {
            $listing->setRentTerm(RentTerm::from($row['rentTerm']));
        }

        return $listing;
    }

    private function existsForAdmin(User $admin, City $city, string $address): bool
    {
        $existing = $this->listingRepository->createQueryBuilder('l')
            ->select('l.id')
            ->andWhere('l.user = :user')
            ->andWhere('l.city = :city')
            ->andWhere('l.address = :address')
            ->andWhere('l.deletedAt IS NULL')
            ->setParameter('user', $admin)
            ->setParameter('city', $city)
            ->setParameter('address', $address)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $existing !== null;
    }
}
