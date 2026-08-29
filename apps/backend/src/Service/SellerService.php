<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Common\PaginatedResponse;
use App\Dto\Listing\ListingSearchRequest;
use App\Dto\Seller\SellerProfileResponse;
use App\Entity\User;
use App\Exception\ResourceNotFoundException;
use App\Http\ApiErrorCode;
use App\Repository\ListingRepository;
use App\Repository\UserRepository;

class SellerService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ListingRepository $listingRepository,
        private readonly ListingService $listingService,
    ) {
    }

    public function getProfile(int $id): SellerProfileResponse
    {
        $user = $this->findPublicSeller($id);
        $count = $this->countPublishedListings($user);

        return SellerProfileResponse::fromEntity($user, $count);
    }

    public function getListings(int $id, int $page = 1, int $limit = 12): PaginatedResponse
    {
        $user = $this->findPublicSeller($id);

        return $this->listingService->search(new ListingSearchRequest(
            userId: $user->getId(),
            page: max(1, $page),
            limit: max(1, min(50, $limit)),
        ));
    }

    private function findPublicSeller(int $id): User
    {
        $user = $this->userRepository->find($id);
        if (!$user instanceof User) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_USER);
        }

        return $user;
    }

    private function countPublishedListings(User $user): int
    {
        $result = $this->listingRepository->search(new ListingSearchRequest(
            userId: $user->getId(),
            page: 1,
            limit: 1,
        ));

        return $result['total'];
    }
}
