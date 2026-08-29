<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\Collection\CollectionOwner;
use App\Entity\Favorite;
use App\Entity\Listing;
use App\Entity\User;
use App\Repository\FavoriteRepository;
use App\Service\FavoriteService;
use App\Service\ListingService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class FavoriteServiceTest extends TestCase
{
    public function testToggleRestoresSoftDeletedFavorite(): void
    {
        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn(7);
        $listing = $this->createStub(Listing::class);
        $listing->method('getId')->willReturn(15);
        $owner = new CollectionOwner($user, null);

        $favorite = (new Favorite())
            ->setUser($user)
            ->setListing($listing)
            ->softDelete();

        $reflection = new \ReflectionProperty(Favorite::class, 'id');
        $reflection->setValue($favorite, 42);

        $repository = $this->createMock(FavoriteRepository::class);
        $repository->expects(self::once())
            ->method('findOneByOwnerAndListingIncludingDeleted')
            ->with($owner, $listing)
            ->willReturn($favorite);

        $listingService = $this->createMock(ListingService::class);
        $listingService->expects(self::once())
            ->method('findEntity')
            ->with(15)
            ->willReturn($listing);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');
        $entityManager->expects(self::never())->method('persist');

        $service = new FavoriteService($repository, $listingService, $entityManager);
        $result = $service->toggle($owner, 15);

        self::assertTrue($result['active']);
        self::assertFalse($favorite->isDeleted());
        self::assertSame(42, $result['item']->id);
        self::assertSame(15, $result['item']->listingId);
    }

    public function testToggleSoftDeletesActiveFavorite(): void
    {
        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn(7);
        $listing = $this->createStub(Listing::class);
        $listing->method('getId')->willReturn(15);
        $owner = new CollectionOwner($user, null);

        $favorite = (new Favorite())
            ->setUser($user)
            ->setListing($listing);

        $repository = $this->createStub(FavoriteRepository::class);
        $repository->method('findOneByOwnerAndListingIncludingDeleted')->willReturn($favorite);

        $listingService = $this->createStub(ListingService::class);
        $listingService->method('findEntity')->willReturn($listing);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $service = new FavoriteService($repository, $listingService, $entityManager);
        $result = $service->toggle($owner, 15);

        self::assertFalse($result['active']);
        self::assertTrue($favorite->isDeleted());
    }
}
