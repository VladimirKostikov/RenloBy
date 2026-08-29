<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DataFixtures\AdminNationwideListingSeedDefinitions;
use App\Entity\City;
use App\Entity\District;
use App\Entity\Listing;
use App\Entity\User;
use App\Enum\DealType;
use App\Enum\ListingType;
use App\Factory\ListingFactory;
use App\Repository\CityRepository;
use App\Repository\DistrictRepository;
use App\Repository\ListingRepository;
use App\Repository\MetroStationRepository;
use App\Repository\UserRepository;
use App\Service\AdminNationwideListingSeeder;
use App\Service\ListingImageCatalog;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;

final class AdminNationwideListingSeederTest extends TestCase
{
    public function testSeedIntoManagerCreatesTestAndPublicCopiesForAdmin(): void
    {
        $admin = new User();
        $minsk = (new City())->setName('Минск')->setSlug('minsk');
        $district = (new District())->setName('Центральный')->setSlug('centralny')->setCity($minsk);

        $citiesBySlug = ['minsk' => $minsk];
        foreach (AdminNationwideListingSeedDefinitions::all() as $row) {
            $slug = $row['citySlug'];
            if (!isset($citiesBySlug[$slug])) {
                $citiesBySlug[$slug] = (new City())->setName($slug)->setSlug($slug);
            }
        }

        $districtsByCity = [];
        foreach ($citiesBySlug as $slug => $city) {
            $districtsByCity[$slug] = [
                (new District())->setName('Центральный')->setSlug('centralny')->setCity($city),
            ];
        }
        $districtsByCity['minsk'] = [$district];

        $factory = $this->createStub(ListingFactory::class);
        $factory->method('create')->willReturnCallback(
            static function (
                User $user,
                City $city,
                District $district,
                DealType $dealType,
                ListingType $listingType,
                $metro,
                bool $isTest,
            ): Listing {
                return (new Listing())
                    ->setUser($user)
                    ->setCity($city)
                    ->setDistrict($district)
                    ->setDealType($dealType)
                    ->setListingType($listingType)
                    ->setIsTest($isTest);
            },
        );

        $imageCatalog = new ListingImageCatalog();

        $seeder = new AdminNationwideListingSeeder(
            $factory,
            $imageCatalog,
            $this->createStub(UserRepository::class),
            $this->createStub(CityRepository::class),
            $this->createStub(DistrictRepository::class),
            $this->createStub(MetroStationRepository::class),
            $this->createStub(ListingRepository::class),
            $this->createStub(EntityManagerInterface::class),
        );

        $manager = $this->createMock(ObjectManager::class);
        $persisted = [];
        $manager->expects(self::exactly(100))->method('persist')->willReturnCallback(
            static function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            },
        );

        $created = $seeder->seedIntoManager($manager, $admin, $citiesBySlug, $districtsByCity, []);

        self::assertCount(100, $created);
        self::assertCount(100, $persisted);

        foreach ($created as $listing) {
            self::assertSame($admin, $listing->getUser());
            self::assertNotSame('', $listing->getAddress());
        }

        $testCount = count(array_filter($created, static fn (Listing $l): bool => $l->isTest()));
        $publicCount = count(array_filter($created, static fn (Listing $l): bool => !$l->isTest()));
        self::assertSame(50, $testCount);
        self::assertSame(50, $publicCount);
    }
}
