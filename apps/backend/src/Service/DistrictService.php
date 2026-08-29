<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\District\CreateDistrictRequest;
use App\Dto\District\DistrictResponse;
use App\Dto\District\UpdateDistrictRequest;
use App\Entity\District;
use App\Exception\ResourceNotFoundException;
use App\Http\ApiErrorCode;
use App\Repository\DistrictRepository;
use Doctrine\ORM\EntityManagerInterface;

class DistrictService
{
    public function __construct(
        private readonly DistrictRepository $districtRepository,
        private readonly CityService $cityService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function list(?int $cityId = null): array
    {
        if ($cityId !== null) {
            $city = $this->cityService->findEntity($cityId);
            $districts = $this->districtRepository->findByCity($city);
        } else {
            $districts = $this->districtRepository->findBy([], ['name' => 'ASC']);
        }

        return array_map(
            fn (District $district) => DistrictResponse::fromEntity($district),
            $districts
        );
    }

    public function get(int $id): DistrictResponse
    {
        return DistrictResponse::fromEntity($this->findEntity($id));
    }

    public function create(CreateDistrictRequest $request): DistrictResponse
    {
        $district = (new District())
            ->setName($request->name)
            ->setSlug($request->slug)
            ->setCity($this->cityService->findEntity($request->cityId));

        if ($request->isTest !== null) {
            $district->setIsTest($request->isTest);
        }

        $this->entityManager->persist($district);
        $this->entityManager->flush();

        return DistrictResponse::fromEntity($district);
    }

    public function update(int $id, UpdateDistrictRequest $request): DistrictResponse
    {
        $district = $this->findEntity($id);

        if ($request->name !== null) {
            $district->setName($request->name);
        }
        if ($request->slug !== null) {
            $district->setSlug($request->slug);
        }
        if ($request->cityId !== null) {
            $district->setCity($this->cityService->findEntity($request->cityId));
        }
        if ($request->isTest !== null) {
            $district->setIsTest($request->isTest);
        }

        $this->entityManager->flush();

        return DistrictResponse::fromEntity($district);
    }

    public function delete(int $id): void
    {
        $district = $this->findEntity($id);
        $district->softDelete();
        $this->entityManager->flush();
    }

    public function findEntity(int $id): District
    {
        $district = $this->districtRepository->find($id);
        if (!$district instanceof District) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_DISTRICT);
        }

        return $district;
    }
}
