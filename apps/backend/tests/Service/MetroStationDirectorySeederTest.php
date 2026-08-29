<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DataFixtures\MetroStationSeedDefinitions;
use App\Entity\City;
use App\Entity\MetroStation;
use App\Factory\MetroStationFactory;
use App\Repository\CityRepository;
use App\Repository\MetroStationRepository;
use App\Service\MetroStationDirectorySeeder;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class MetroStationDirectorySeederTest extends TestCase
{
    public function testSyncMinskCreatesMissingStations(): void
    {
        $minsk = $this->createConfiguredMock(City::class, ['getSlug' => 'minsk']);
        $existing = (new MetroStation())
            ->setName('Немига')
            ->setSlug('nemiga')
            ->setLineColor('#0072BC')
            ->setCity($minsk);

        $cities = $this->createMock(CityRepository::class);
        $cities->method('findOneBy')->with(['slug' => 'minsk'])->willReturn($minsk);

        $metros = $this->createMock(MetroStationRepository::class);
        $metros->method('findOneBy')->willReturnCallback(
            static function (array $criteria) use ($existing): ?MetroStation {
                if (($criteria['slug'] ?? null) === 'nemiga') {
                    return $existing;
                }

                return null;
            }
        );
        $metros->method('findOneByCityAndNameIgnoreCase')->willReturn(null);

        $created = [];
        $factory = $this->createMock(MetroStationFactory::class);
        $factory->method('create')->willReturnCallback(
            static function (City $city, string $name, string $slug, string $color) use (&$created): MetroStation {
                $station = (new MetroStation())
                    ->setName($name)
                    ->setSlug($slug)
                    ->setLineColor($color)
                    ->setCity($city);
                $created[] = $slug;

                return $station;
            }
        );

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::exactly(count(MetroStationSeedDefinitions::minskStations()) - 1))
            ->method('persist');
        $em->expects(self::once())->method('flush');

        $seeder = new MetroStationDirectorySeeder($cities, $metros, $factory, $em);
        $result = $seeder->syncMinsk();

        self::assertSame(35, $result['created']);
        self::assertSame(1, $result['updated']);
        self::assertNotContains('nemiga', $created);
        self::assertContains('malinovka', $created);
        self::assertSame(MetroStationSeedDefinitions::LINE_RED, $existing->getLineColor());
    }
}
