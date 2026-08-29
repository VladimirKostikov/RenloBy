<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Listing;
use App\Entity\User;
use App\Enum\ListingStatus;
use App\Exception\ForbiddenException;
use App\Service\ListingGoodPriceEvaluator;
use App\Service\ListingService;
use App\Service\LocationTextResolver;
use App\Service\SellerListingService;
use App\Service\SellerProfileGate;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class SellerListingServiceDeleteTest extends TestCase
{
    public function testDeleteAllowsPendingListing(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(7);

        $listing = $this->createMock(Listing::class);
        $listing->method('getUser')->willReturn($user);
        $listing->method('getStatus')->willReturn(ListingStatus::Pending);
        $listing->expects(self::once())->method('softDelete')->willReturnSelf();

        $listingService = $this->createMock(ListingService::class);
        $listingService->method('findEntity')->with(15)->willReturn($listing);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $service = new SellerListingService(
            $listingService,
            $this->createMock(LocationTextResolver::class),
            $em,
            $this->createMock(SellerProfileGate::class),
            $this->createMock(ListingGoodPriceEvaluator::class),
        );

        $service->deleteRemovable($user, 15);
    }

    public function testDeleteRejectsPublishedListing(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(7);

        $listing = $this->createMock(Listing::class);
        $listing->method('getUser')->willReturn($user);
        $listing->method('getStatus')->willReturn(ListingStatus::Published);
        $listing->expects(self::never())->method('softDelete');

        $listingService = $this->createMock(ListingService::class);
        $listingService->method('findEntity')->with(15)->willReturn($listing);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $service = new SellerListingService(
            $listingService,
            $this->createMock(LocationTextResolver::class),
            $em,
            $this->createMock(SellerProfileGate::class),
            $this->createMock(ListingGoodPriceEvaluator::class),
        );

        $this->expectException(ForbiddenException::class);
        $service->deleteRemovable($user, 15);
    }
}
