<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\MetroStation\CreateMetroStationRequest;
use App\Dto\MetroStation\MetroStationResponse;
use App\Dto\MetroStation\UpdateMetroStationRequest;
use App\Entity\MetroStation;
use App\Exception\ResourceNotFoundException;
use App\Http\ApiErrorCode;
use App\Repository\MetroStationRepository;
use Doctrine\ORM\EntityManagerInterface;

class MetroStationService
{
    public function __construct(
        private readonly MetroStationRepository $metroStationRepository,
        private readonly CityService $cityService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function list(?int $cityId = null): array
    {
        if ($cityId !== null) {
            $city = $this->cityService->findEntity($cityId);
            $stations = $this->metroStationRepository->findByCity($city);
        } else {
            $stations = $this->metroStationRepository->findBy([], ['name' => 'ASC']);
        }

        return array_map(
            fn (MetroStation $station) => MetroStationResponse::fromEntity($station),
            $stations
        );
    }

    public function get(int $id): MetroStationResponse
    {
        return MetroStationResponse::fromEntity($this->findEntity($id));
    }

    public function create(CreateMetroStationRequest $request): MetroStationResponse
    {
        $station = (new MetroStation())
            ->setName($request->name)
            ->setSlug($request->slug)
            ->setLineColor($request->lineColor)
            ->setCity($this->cityService->findEntity($request->cityId));

        if ($request->isTest !== null) {
            $station->setIsTest($request->isTest);
        }

        $this->entityManager->persist($station);
        $this->entityManager->flush();

        return MetroStationResponse::fromEntity($station);
    }

    public function update(int $id, UpdateMetroStationRequest $request): MetroStationResponse
    {
        $station = $this->findEntity($id);

        if ($request->name !== null) {
            $station->setName($request->name);
        }
        if ($request->slug !== null) {
            $station->setSlug($request->slug);
        }
        if ($request->lineColor !== null) {
            $station->setLineColor($request->lineColor);
        }
        if ($request->cityId !== null) {
            $station->setCity($this->cityService->findEntity($request->cityId));
        }
        if ($request->isTest !== null) {
            $station->setIsTest($request->isTest);
        }

        $this->entityManager->flush();

        return MetroStationResponse::fromEntity($station);
    }

    public function delete(int $id): void
    {
        $station = $this->findEntity($id);
        $station->softDelete();
        $this->entityManager->flush();
    }

    public function findEntity(int $id): MetroStation
    {
        $station = $this->metroStationRepository->find($id);
        if (!$station instanceof MetroStation) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_METRO_STATION);
        }

        return $station;
    }
}
