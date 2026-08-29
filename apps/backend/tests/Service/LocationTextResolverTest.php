<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\City;
use App\Repository\CityRepository;
use App\Repository\DistrictRepository;
use App\Repository\MetroStationRepository;
use App\Service\LocationTextResolver;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class LocationTextResolverTest extends TestCase
{
    public function testNormalizeNameTrimsAndCollapsesSpaces(): void
    {
        $resolver = new LocationTextResolver(
            $this->createMock(CityRepository::class),
            $this->createMock(DistrictRepository::class),
            $this->createMock(MetroStationRepository::class),
            $this->createMock(EntityManagerInterface::class),
        );

        self::assertSame('Минск', $resolver->normalizeName('  Минск  '));
        self::assertSame('Центральный район', $resolver->normalizeName("Центральный   район"));
    }

    public function testResolveCityReusesExistingByName(): void
    {
        $existing = (new City())->setName('Минск')->setSlug('minsk')->setRegionSlug('minsk-city');
        $cities = $this->createMock(CityRepository::class);
        $cities->method('findOneByNameIgnoreCase')->with('минск')->willReturn($existing);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('persist');

        $resolver = new LocationTextResolver(
            $cities,
            $this->createMock(DistrictRepository::class),
            $this->createMock(MetroStationRepository::class),
            $em,
        );

        self::assertSame($existing, $resolver->resolveCity(' минск '));
    }

    public function testResolveCityCreatesWhenMissing(): void
    {
        $cities = $this->createMock(CityRepository::class);
        $cities->method('findOneByNameIgnoreCase')->willReturn(null);
        $cities->method('findOneBySlug')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with(self::isInstanceOf(City::class));
        $em->expects(self::once())->method('flush');

        $resolver = new LocationTextResolver(
            $cities,
            $this->createMock(DistrictRepository::class),
            $this->createMock(MetroStationRepository::class),
            $em,
        );

        $city = $resolver->resolveCity('Орша');
        self::assertSame('Орша', $city->getName());
        self::assertNotSame('', $city->getSlug());
    }

    public function testResolveMetroStationUsesProvidedLineColorForNewStation(): void
    {
        $city = (new City())->setName('Минск')->setSlug('minsk')->setRegionSlug('minsk-city');
        $metros = $this->createMock(MetroStationRepository::class);
        $metros->method('findOneByCityAndNameIgnoreCase')->willReturn(null);
        $metros->method('findOneBy')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with(self::callback(
            static function (object $entity): bool {
                return $entity instanceof \App\Entity\MetroStation
                    && $entity->getLineColor() === '#D62027';
            },
        ));
        $em->expects(self::once())->method('flush');

        $resolver = new LocationTextResolver(
            $this->createMock(CityRepository::class),
            $this->createMock(DistrictRepository::class),
            $metros,
            $em,
        );

        $station = $resolver->resolveMetroStation($city, 'Новая', true, '#D62027');
        self::assertNotNull($station);
        self::assertSame('#D62027', $station->getLineColor());
    }

    public function testNormalizeLineColor(): void
    {
        $resolver = new LocationTextResolver(
            $this->createMock(CityRepository::class),
            $this->createMock(DistrictRepository::class),
            $this->createMock(MetroStationRepository::class),
            $this->createMock(EntityManagerInterface::class),
        );

        self::assertSame('#0072BC', $resolver->normalizeLineColor(null));
        self::assertSame('#009A49', $resolver->normalizeLineColor('#009a49'));
        self::assertSame('#0072BC', $resolver->normalizeLineColor('red'));
    }
}
