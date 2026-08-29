<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Listing;
use App\Entity\ListingRequest;
use App\Entity\User;
use App\Enum\ListingRequestStatus;
use App\Exception\ForbiddenException;
use App\Http\ApiErrorCode;
use App\Repository\ListingRequestRepository;
use App\Service\ListingAnalyticsRecorder;
use App\Service\ListingRequestService;
use App\Service\ListingService;
use App\Service\UserNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ListingRequestServiceDeleteForSellerTest extends TestCase
{
    public function testDeleteForSellerSoftDeletesOwnRequest(): void
    {
        $owner = $this->createMock(User::class);
        $owner->method('getId')->willReturn(5);

        $listing = $this->createMock(Listing::class);
        $listing->method('getUser')->willReturn($owner);

        $request = (new ListingRequest())
            ->setListing($listing)
            ->setPhone('+375291112233')
            ->setMessage('Хочу посмотреть квартиру завтра утром.')
            ->setStatus(ListingRequestStatus::New)
            ->setIsTest(true);

        $repo = $this->createMock(ListingRequestRepository::class);
        $repo->method('find')->with(11)->willReturn($request);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $service = new ListingRequestService(
            $repo,
            $this->createMock(ListingService::class),
            $this->createMock(UserNotificationService::class),
            $this->createMock(ListingAnalyticsRecorder::class),
            $em,
        );

        $service->deleteForSeller($owner, 11);

        self::assertTrue($request->isDeleted());
        self::assertNotNull($request->getDeletedAt());
    }

    public function testDeleteForSellerForbidsOtherOwner(): void
    {
        $owner = $this->createMock(User::class);
        $owner->method('getId')->willReturn(5);

        $other = $this->createMock(User::class);
        $other->method('getId')->willReturn(9);

        $listing = $this->createMock(Listing::class);
        $listing->method('getUser')->willReturn($owner);

        $request = (new ListingRequest())
            ->setListing($listing)
            ->setPhone('+375291112233')
            ->setMessage('Хочу посмотреть квартиру завтра утром.')
            ->setStatus(ListingRequestStatus::New)
            ->setIsTest(true);

        $repo = $this->createMock(ListingRequestRepository::class);
        $repo->method('find')->with(11)->willReturn($request);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $service = new ListingRequestService(
            $repo,
            $this->createMock(ListingService::class),
            $this->createMock(UserNotificationService::class),
            $this->createMock(ListingAnalyticsRecorder::class),
            $em,
        );

        try {
            $service->deleteForSeller($other, 11);
            self::fail('Expected ForbiddenException');
        } catch (ForbiddenException $exception) {
            self::assertSame(ApiErrorCode::FORBIDDEN_LISTING_REQUEST, $exception->getMessage());
        }

        self::assertFalse($request->isDeleted());
    }
}
