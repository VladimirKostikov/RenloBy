<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\City;
use App\Entity\District;
use App\Entity\MetroStation;
use App\Repository\CityRepository;
use App\Repository\DistrictRepository;
use App\Repository\MetroStationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

class LocationTextResolver
{
    private readonly AsciiSlugger $slugger;

    public function __construct(
        private readonly CityRepository $cityRepository,
        private readonly DistrictRepository $districtRepository,
        private readonly MetroStationRepository $metroStationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->slugger = new AsciiSlugger('ru');
    }

    public function resolveCity(string $name, bool $isTest = true): City
    {
        $normalized = $this->normalizeName($name);
        $existing = $this->cityRepository->findOneByNameIgnoreCase($normalized);
        if ($existing instanceof City) {
            return $existing;
        }

        $city = (new City())
            ->setName($normalized)
            ->setSlug($this->uniqueCitySlug($normalized))
            ->setRegionSlug('other')
            ->setIsTest($isTest);

        $this->entityManager->persist($city);
        $this->entityManager->flush();

        return $city;
    }

    public function resolveDistrict(City $city, string $name, bool $isTest = true): District
    {
        $normalized = $this->normalizeName($name);
        $existing = $this->districtRepository->findOneByCityAndNameIgnoreCase($city, $normalized);
        if ($existing instanceof District) {
            return $existing;
        }

        $district = (new District())
            ->setCity($city)
            ->setName($normalized)
            ->setSlug($this->uniqueDistrictSlug($city, $normalized))
            ->setIsTest($isTest);

        $this->entityManager->persist($district);
        $this->entityManager->flush();

        return $district;
    }

    public function resolveMetroStation(
        City $city,
        ?string $name,
        bool $isTest = true,
        ?string $lineColor = null,
    ): ?MetroStation {
        if ($name === null) {
            return null;
        }

        $normalized = $this->normalizeName($name);
        if ($normalized === '') {
            return null;
        }

        $existing = $this->metroStationRepository->findOneByCityAndNameIgnoreCase($city, $normalized);
        if ($existing instanceof MetroStation) {
            return $existing;
        }

        $station = (new MetroStation())
            ->setCity($city)
            ->setName($normalized)
            ->setSlug($this->uniqueMetroSlug($city, $normalized))
            ->setLineColor($this->normalizeLineColor($lineColor))
            ->setIsTest($isTest);

        $this->entityManager->persist($station);
        $this->entityManager->flush();

        return $station;
    }

    public function normalizeLineColor(?string $color): string
    {
        if ($color === null) {
            return '#0072BC';
        }

        $trimmed = trim($color);
        if (preg_match('/^#?([0-9A-Fa-f]{6})$/', $trimmed, $matches) !== 1) {
            return '#0072BC';
        }

        return '#' . strtoupper($matches[1]);
    }

    public function normalizeName(string $name): string
    {
        $trimmed = trim(preg_replace('/\s+/u', ' ', $name) ?? '');

        return mb_substr($trimmed, 0, 120);
    }

    private function uniqueCitySlug(string $name): string
    {
        $base = $this->toSlug($name);
        $slug = $base;
        $i = 2;
        while ($this->cityRepository->findOneBySlug($slug) instanceof City) {
            $slug = $base . '-' . $i;
            ++$i;
        }

        return $slug;
    }

    private function uniqueDistrictSlug(City $city, string $name): string
    {
        $base = $this->toSlug($name);
        $slug = $base;
        $i = 2;
        while ($this->districtRepository->findOneBy(['city' => $city, 'slug' => $slug]) instanceof District) {
            $slug = $base . '-' . $i;
            ++$i;
        }

        return $slug;
    }

    private function uniqueMetroSlug(City $city, string $name): string
    {
        $base = $this->toSlug($name);
        $slug = $base;
        $i = 2;
        while ($this->metroStationRepository->findOneBy(['city' => $city, 'slug' => $slug]) instanceof MetroStation) {
            $slug = $base . '-' . $i;
            ++$i;
        }

        return $slug;
    }

    private function toSlug(string $name): string
    {
        $slug = strtolower($this->slugger->slug($name)->toString());
        if ($slug === '') {
            return 'location-' . substr(md5($name), 0, 8);
        }

        return mb_substr($slug, 0, 80);
    }
}
