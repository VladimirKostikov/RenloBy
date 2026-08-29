<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Dto\Listing\ListingSearchRequest;
use App\Entity\City;
use App\Entity\District;
use App\Entity\Listing;
use App\Entity\User;
use App\Enum\DealType;
use App\Enum\ListingType;
use App\Repository\ListingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ListingRepositorySearchTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private ListingRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->repository = static::getContainer()->get(ListingRepository::class);
    }

    public function testFiltersSaleListingsByFromOwnerAndRenovation(): void
    {
        $user = $this->createUser('sale-filter-user@renlo.local');
        $city = $this->createCity('Filter City', 'filter-city');
        $district = $this->createDistrict($city, 'Filter District', 'filter-district');

        $matching = $this->createListing($user, $city, $district, DealType::Sale, true, true, true);
        $this->createListing($user, $city, $district, DealType::Sale, false, true, true);
        $this->createListing($user, $city, $district, DealType::Sale, true, false, true);

        $this->entityManager->flush();
        $this->entityManager->clear();

        $result = $this->repository->search(new ListingSearchRequest(
            dealType: DealType::Sale,
            fromOwner: true,
            hasRenovation: true,
            noCommission: true,
            cityId: $city->getId(),
        ));

        self::assertSame(1, $result['total']);
        self::assertCount(1, $result['items']);
        self::assertSame($matching->getId(), $result['items'][0]->getId());
    }

    public function testIncludesDraftsWhenIncludeNonPublished(): void
    {
        $user = $this->createUser('draft-admin-user@renlo.local');
        $city = $this->createCity('Draft City', 'draft-city');
        $district = $this->createDistrict($city, 'Draft District', 'draft-district');

        $draft = $this->createListing($user, $city, $district, DealType::Sale, true, true, true);
        $draft->setStatus(\App\Enum\ListingStatus::Draft);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $hidden = $this->repository->search(new ListingSearchRequest(
            cityId: $city->getId(),
        ));
        self::assertSame(0, $hidden['total']);

        $visible = $this->repository->search(new ListingSearchRequest(
            cityId: $city->getId(),
            includeNonPublished: true,
        ));
        self::assertSame(1, $visible['total']);
        self::assertSame($draft->getId(), $visible['items'][0]->getId());
    }

    public function testRandomSortReturnsPublishedListings(): void
    {
        $user = $this->createUser('random-sort-user@renlo.local');
        $city = $this->createCity('Random City', 'random-city');
        $district = $this->createDistrict($city, 'Random District', 'random-district');

        $this->createListing($user, $city, $district, DealType::Sale, true, true, true);
        $this->createListing($user, $city, $district, DealType::Sale, false, false, false);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $result = $this->repository->search(new ListingSearchRequest(
            cityId: $city->getId(),
            sort: 'random',
            limit: 10,
        ));

        self::assertSame(2, $result['total']);
        self::assertCount(2, $result['items']);
    }

    private function createUser(string $email): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setName('Filter User')
            ->setRoles(['ROLE_USER'])
            ->setPassword('hashed');

        $this->entityManager->persist($user);

        return $user;
    }

    private function createCity(string $name, string $slug, string $regionSlug = 'minsk-region'): City
    {
        $city = (new City())
            ->setName($name)
            ->setSlug($slug)
            ->setRegionSlug($regionSlug);

        $this->entityManager->persist($city);

        return $city;
    }

    private function createDistrict(City $city, string $name, string $slug): District
    {
        $district = (new District())
            ->setCity($city)
            ->setName($name)
            ->setSlug($slug);

        $this->entityManager->persist($district);

        return $district;
    }

    private function createListing(
        User $user,
        City $city,
        District $district,
        DealType $dealType,
        bool $fromOwner,
        bool $hasRenovation,
        bool $noCommission,
    ): Listing {
        $listing = (new Listing())
            ->setUser($user)
            ->setCity($city)
            ->setDistrict($district)
            ->setDealType($dealType)
            ->setListingType(ListingType::Apartment)
            ->setPrice(120000)
            ->setPricePerSqm(2400)
            ->setRooms(2)
            ->setArea(50.0)
            ->setFloor(5)
            ->setTotalFloors(12)
            ->setAddress('Filter street, 1')
            ->setLatitude(53.9)
            ->setLongitude(27.5)
            ->setVerified(true)
            ->setFromOwner($fromOwner)
            ->setHasRenovation($hasRenovation)
            ->setNoCommission($noCommission)
            ->setImages(['https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=640'])
            ->setPublishedAt(new \DateTimeImmutable('-1 day'));

        $this->entityManager->persist($listing);

        return $listing;
    }
}
