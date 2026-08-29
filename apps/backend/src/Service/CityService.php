<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\City\CityResponse;
use App\Dto\City\CreateCityRequest;
use App\Dto\City\UpdateCityRequest;
use App\Entity\City;
use App\Exception\ResourceNotFoundException;
use App\Http\ApiErrorCode;
use App\Repository\CityRepository;
use Doctrine\ORM\EntityManagerInterface;

class CityService
{
    public function __construct(
        private readonly CityRepository $cityRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function list(): array
    {
        return array_map(
            fn (City $city) => CityResponse::fromEntity($city),
            $this->cityRepository->findBy([], ['name' => 'ASC'])
        );
    }

    public function get(int $id): CityResponse
    {
        return CityResponse::fromEntity($this->findEntity($id));
    }

    public function create(CreateCityRequest $request): CityResponse
    {
        $city = (new City())
            ->setName($request->name)
            ->setSlug($request->slug)
            ->setRegionSlug($request->regionSlug);

        if ($request->isTest !== null) {
            $city->setIsTest($request->isTest);
        }

        $this->entityManager->persist($city);
        $this->entityManager->flush();

        return CityResponse::fromEntity($city);
    }

    public function update(int $id, UpdateCityRequest $request): CityResponse
    {
        $city = $this->findEntity($id);

        if ($request->name !== null) {
            $city->setName($request->name);
        }
        if ($request->slug !== null) {
            $city->setSlug($request->slug);
        }
        if ($request->regionSlug !== null) {
            $city->setRegionSlug($request->regionSlug);
        }
        if ($request->isTest !== null) {
            $city->setIsTest($request->isTest);
        }

        $this->entityManager->flush();

        return CityResponse::fromEntity($city);
    }

    public function delete(int $id): void
    {
        $city = $this->findEntity($id);
        $city->softDelete();
        $this->entityManager->flush();
    }

    public function findEntity(int $id): City
    {
        $city = $this->cityRepository->find($id);
        if (!$city instanceof City) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_CITY);
        }

        return $city;
    }
}
