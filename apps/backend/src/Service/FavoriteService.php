<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Collection\CollectionOwner;
use App\Dto\Favorite\CreateFavoriteRequest;
use App\Dto\Favorite\FavoriteItemResponse;
use App\Dto\Favorite\FavoriteResponse;
use App\Dto\Listing\ListingResponse;
use App\Entity\Favorite;
use App\Entity\Listing;
use App\Exception\ResourceNotFoundException;
use App\Http\ApiErrorCode;
use App\Repository\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;

class FavoriteService
{
    public function __construct(
        private readonly FavoriteRepository $favoriteRepository,
        private readonly ListingService $listingService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function list(CollectionOwner $owner): array
    {
        return array_map(
            fn (Favorite $favorite) => FavoriteResponse::fromEntity($favorite),
            $this->favoriteRepository->findByOwner($owner)
        );
    }

    public function listWithListings(CollectionOwner $owner): array
    {
        return array_map(
            function (Favorite $favorite): FavoriteItemResponse {
                $listing = $favorite->getListing();
                if ($listing === null) {
                    throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_LISTING);
                }

                return FavoriteItemResponse::fromEntity(
                    $favorite,
                    ListingResponse::fromEntity($listing)
                );
            },
            $this->favoriteRepository->findByOwner($owner)
        );
    }

    public function add(CollectionOwner $owner, CreateFavoriteRequest $request): FavoriteResponse
    {
        $listing = $this->listingService->findEntity($request->listingId);
        $existing = $this->favoriteRepository->findOneByOwnerAndListingIncludingDeleted($owner, $listing);

        if ($existing instanceof Favorite) {
            if ($existing->isDeleted()) {
                $existing->restore();
                $this->entityManager->flush();
            }

            return FavoriteResponse::fromEntity($existing);
        }

        $favorite = $this->createFavorite($owner, $listing);

        $this->entityManager->persist($favorite);
        $this->entityManager->flush();

        return FavoriteResponse::fromEntity($favorite);
    }

    /**
     * @return array{active: bool, item?: FavoriteResponse}
     */
    public function toggle(CollectionOwner $owner, int $listingId): array
    {
        $listing = $this->listingService->findEntity($listingId);
        $existing = $this->favoriteRepository->findOneByOwnerAndListingIncludingDeleted($owner, $listing);

        if ($existing instanceof Favorite) {
            if (!$existing->isDeleted()) {
                $existing->softDelete();
                $this->entityManager->flush();

                return ['active' => false];
            }

            $existing->restore();
            $this->entityManager->flush();

            return [
                'active' => true,
                'item' => FavoriteResponse::fromEntity($existing),
            ];
        }

        $favorite = $this->createFavorite($owner, $listing);

        $this->entityManager->persist($favorite);
        $this->entityManager->flush();

        return [
            'active' => true,
            'item' => FavoriteResponse::fromEntity($favorite),
        ];
    }

    public function remove(CollectionOwner $owner, int $id): void
    {
        $favorite = $this->favoriteRepository->find($id);
        if (!$favorite instanceof Favorite || !$this->ownsFavorite($owner, $favorite)) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_FAVORITE);
        }

        $favorite->softDelete();
        $this->entityManager->flush();
    }

    private function createFavorite(CollectionOwner $owner, Listing $listing): Favorite
    {
        $favorite = (new Favorite())->setListing($listing);

        if ($owner->user !== null) {
            return $favorite->setUser($owner->user);
        }

        return $favorite->setGuestSessionHash((string) $owner->guestSessionHash);
    }

    private function ownsFavorite(CollectionOwner $owner, Favorite $favorite): bool
    {
        if ($owner->user !== null) {
            return $favorite->getUser()?->getId() === $owner->user->getId();
        }

        return $favorite->getGuestSessionHash() === $owner->guestSessionHash;
    }
}
