<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\ListingRequest\ListingRequestResponse;
use App\Entity\Listing;
use App\Entity\ListingRequest;
use App\Entity\User;
use App\Enum\ListingRequestStatus;
use App\Repository\ListingRequestRepository;
use App\Service\ListingAnalyticsRecorder;
use App\Service\ListingRequestService;
use App\Service\ListingService;
use App\Service\UserNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ListingRequestServiceSellerListTest extends TestCase
{
    public function testListForSellerMapsEntities(): void
    {
        $user = $this->createMock(User::class);
        $listing = $this->createMock(Listing::class);
        $listing->method('getId')->willReturn(7);
        $listing->method('getAddress')->willReturn('ул. Тестовая, 1');

        $request = (new ListingRequest())
            ->setListing($listing)
            ->setPhone('+375291112233')
            ->setMessage('Хочу посмотреть квартиру завтра утром.')
            ->setStatus(ListingRequestStatus::New)
            ->setIsTest(true);

        $repo = $this->createMock(ListingRequestRepository::class);
        $repo->expects(self::once())
            ->method('findByListingOwner')
            ->with($user)
            ->willReturn([$request]);

        $service = new ListingRequestService(
            $repo,
            $this->createMock(ListingService::class),
            $this->createMock(UserNotificationService::class),
            $this->createMock(ListingAnalyticsRecorder::class),
            $this->createMock(EntityManagerInterface::class),
        );

        $items = $service->listForSeller($user);
        self::assertCount(1, $items);
        self::assertInstanceOf(ListingRequestResponse::class, $items[0]);
        self::assertSame('+375291112233', $items[0]->phone);
        self::assertSame(ListingRequestStatus::New, $items[0]->status);
    }
}
