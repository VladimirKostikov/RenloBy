<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Collection\CollectionOwner;
use App\Dto\Comparison\ComparisonItemResponse;
use App\Dto\Comparison\ComparisonResponse;
use App\Dto\Comparison\CreateComparisonRequest;
use App\Dto\Listing\ListingResponse;
use App\Entity\Comparison;
use App\Entity\Listing;
use App\Exception\ResourceNotFoundException;
use App\Exception\ValidationException;
use App\Http\ApiErrorCode;
use App\Repository\ComparisonRepository;
use Doctrine\ORM\EntityManagerInterface;

class ComparisonService
{
    public const MAX_ITEMS = 4;

    public function __construct(
        private readonly ComparisonRepository $comparisonRepository,
        private readonly ListingService $listingService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function list(CollectionOwner $owner): array
    {
        return array_map(
            fn (Comparison $comparison) => ComparisonResponse::fromEntity($comparison),
            $this->comparisonRepository->findByOwner($owner)
        );
    }

    public function listWithListings(CollectionOwner $owner): array
    {
        return array_map(
            function (Comparison $comparison): ComparisonItemResponse {
                $listing = $comparison->getListing();
                if ($listing === null) {
                    throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_LISTING);
                }

                return ComparisonItemResponse::fromEntity(
                    $comparison,
                    ListingResponse::fromEntity($listing)
                );
            },
            $this->comparisonRepository->findByOwner($owner)
        );
    }

    public function add(CollectionOwner $owner, CreateComparisonRequest $request): ComparisonResponse
    {
        $listing = $this->listingService->findEntity($request->listingId);
        $existing = $this->comparisonRepository->findOneByOwnerAndListingIncludingDeleted($owner, $listing);

        if ($existing instanceof Comparison) {
            if ($existing->isDeleted()) {
                $this->assertCanAdd($owner);
                $existing->restore();
                $this->entityManager->flush();
            }

            return ComparisonResponse::fromEntity($existing);
        }

        $this->assertCanAdd($owner);

        $comparison = $this->createComparison($owner, $listing);

        $this->entityManager->persist($comparison);
        $this->entityManager->flush();

        return ComparisonResponse::fromEntity($comparison);
    }

    /**
     * @return array{active: bool, item?: ComparisonResponse}
     */
    public function toggle(CollectionOwner $owner, int $listingId): array
    {
        $listing = $this->listingService->findEntity($listingId);
        $existing = $this->comparisonRepository->findOneByOwnerAndListingIncludingDeleted($owner, $listing);

        if ($existing instanceof Comparison) {
            if (!$existing->isDeleted()) {
                $existing->softDelete();
                $this->entityManager->flush();

                return ['active' => false];
            }

            $this->assertCanAdd($owner);
            $existing->restore();
            $this->entityManager->flush();

            return [
                'active' => true,
                'item' => ComparisonResponse::fromEntity($existing),
            ];
        }

        $this->assertCanAdd($owner);

        $comparison = $this->createComparison($owner, $listing);

        $this->entityManager->persist($comparison);
        $this->entityManager->flush();

        return [
            'active' => true,
            'item' => ComparisonResponse::fromEntity($comparison),
        ];
    }

    public function remove(CollectionOwner $owner, int $id): void
    {
        $comparison = $this->comparisonRepository->find($id);
        if (!$comparison instanceof Comparison || !$this->ownsComparison($owner, $comparison)) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_COMPARISON);
        }

        $comparison->softDelete();
        $this->entityManager->flush();
    }

    private function assertCanAdd(CollectionOwner $owner): void
    {
        if (count($this->comparisonRepository->findByOwner($owner)) >= self::MAX_ITEMS) {
            throw new ValidationException([
                'listingId' => ApiErrorCode::COMPARISON_LIMIT_REACHED,
            ]);
        }
    }

    private function createComparison(CollectionOwner $owner, Listing $listing): Comparison
    {
        $comparison = (new Comparison())->setListing($listing);

        if ($owner->user !== null) {
            return $comparison->setUser($owner->user);
        }

        return $comparison->setGuestSessionHash((string) $owner->guestSessionHash);
    }

    private function ownsComparison(CollectionOwner $owner, Comparison $comparison): bool
    {
        if ($owner->user !== null) {
            return $comparison->getUser()?->getId() === $owner->user->getId();
        }

        return $comparison->getGuestSessionHash() === $owner->guestSessionHash;
    }
}
