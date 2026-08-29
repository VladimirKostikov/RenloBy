<?php

declare(strict_types=1);

namespace App\Service;

use App\DataFixtures\MetroStationSeedDefinitions;
use App\Entity\City;
use App\Entity\MetroStation;
use App\Factory\MetroStationFactory;
use App\Repository\CityRepository;
use App\Repository\MetroStationRepository;
use Doctrine\ORM\EntityManagerInterface;

final class MetroStationDirectorySeeder
{
    public function __construct(
        private readonly CityRepository $cityRepository,
        private readonly MetroStationRepository $metroStationRepository,
        private readonly MetroStationFactory $metroStationFactory,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{created: int, updated: int}
     */
    public function syncMinsk(): array
    {
        $minsk = $this->cityRepository->findOneBy(['slug' => 'minsk']);
        if (!$minsk instanceof City) {
            return ['created' => 0, 'updated' => 0];
        }

        $created = 0;
        $updated = 0;

        foreach (MetroStationSeedDefinitions::minskStations() as [$name, $slug, $color]) {
            $station = $this->findExistingStation($minsk, $name, $slug);
            if ($station instanceof MetroStation) {
                $changed = false;
                if ($station->getName() !== $name) {
                    $station->setName($name);
                    $changed = true;
                }
                if ($station->getSlug() !== $slug) {
                    $station->setSlug($slug);
                    $changed = true;
                }
                if ($station->getLineColor() !== $color) {
                    $station->setLineColor($color);
                    $changed = true;
                }
                if ($station->isDeleted()) {
                    $station->restore();
                    $changed = true;
                }
                if ($changed) {
                    ++$updated;
                }
                continue;
            }

            $this->entityManager->persist(
                $this->metroStationFactory->create($minsk, $name, $slug, $color)
            );
            ++$created;
        }

        $this->entityManager->flush();

        return ['created' => $created, 'updated' => $updated];
    }

    private function findExistingStation(City $city, string $name, string $slug): ?MetroStation
    {
        $bySlug = $this->metroStationRepository->findOneBy(['city' => $city, 'slug' => $slug]);
        if ($bySlug instanceof MetroStation) {
            return $bySlug;
        }

        return $this->metroStationRepository->findOneByCityAndNameIgnoreCase($city, $name);
    }
}
