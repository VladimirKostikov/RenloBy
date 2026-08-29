<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\SavedSearch\CreateSavedSearchRequest;
use App\Dto\SavedSearch\SavedSearchResponse;
use App\Dto\SavedSearch\UpdateSavedSearchRequest;
use App\Entity\SavedSearch;
use App\Entity\User;
use App\Exception\ResourceNotFoundException;
use App\Http\ApiErrorCode;
use App\Repository\SavedSearchRepository;
use Doctrine\ORM\EntityManagerInterface;

class SavedSearchService
{
    public function __construct(
        private readonly SavedSearchRepository $savedSearchRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function list(User $user): array
    {
        return array_map(
            fn (SavedSearch $savedSearch) => SavedSearchResponse::fromEntity($savedSearch),
            $this->savedSearchRepository->findByUser($user)
        );
    }

    public function get(User $user, int $id): SavedSearchResponse
    {
        return SavedSearchResponse::fromEntity($this->findOwnedEntity($user, $id));
    }

    public function create(User $user, CreateSavedSearchRequest $request): SavedSearchResponse
    {
        $savedSearch = (new SavedSearch())
            ->setName($request->name)
            ->setFilters($request->filters)
            ->setUser($user);

        $this->entityManager->persist($savedSearch);
        $this->entityManager->flush();

        return SavedSearchResponse::fromEntity($savedSearch);
    }

    public function update(User $user, int $id, UpdateSavedSearchRequest $request): SavedSearchResponse
    {
        $savedSearch = $this->findOwnedEntity($user, $id);

        if ($request->name !== null) {
            $savedSearch->setName($request->name);
        }
        if ($request->filters !== null) {
            $savedSearch->setFilters($request->filters);
        }

        $this->entityManager->flush();

        return SavedSearchResponse::fromEntity($savedSearch);
    }

    public function delete(User $user, int $id): void
    {
        $savedSearch = $this->findOwnedEntity($user, $id);
        $savedSearch->softDelete();
        $this->entityManager->flush();
    }

    private function findOwnedEntity(User $user, int $id): SavedSearch
    {
        $savedSearch = $this->savedSearchRepository->find($id);
        if (!$savedSearch instanceof SavedSearch || $savedSearch->getUser()?->getId() !== $user->getId()) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_SAVED_SEARCH);
        }

        return $savedSearch;
    }
}
