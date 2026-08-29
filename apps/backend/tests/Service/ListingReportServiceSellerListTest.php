<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\ListingReport\ListingReportResponse;
use App\Entity\Listing;
use App\Entity\ListingReport;
use App\Entity\User;
use App\Enum\ListingReportReason;
use App\Enum\ListingReportStatus;
use App\Repository\ListingReportRepository;
use App\Service\ListingReportService;
use App\Service\ListingService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ListingReportServiceSellerListTest extends TestCase
{
    public function testListForSellerMapsOwnedReports(): void
    {
        $user = $this->createMock(User::class);
        $listing = $this->createMock(Listing::class);
        $listing->method('getId')->willReturn(42);
        $listing->method('getAddress')->willReturn('ул. Тестовая, 1');
        $listing->method('isTest')->willReturn(false);

        $report = (new ListingReport())
            ->setListing($listing)
            ->setReason(ListingReportReason::Spam)
            ->setComment('Спам в описании объявления и контактах.')
            ->setStatus(ListingReportStatus::New)
            ->setIsTest(false);

        $repo = $this->createMock(ListingReportRepository::class);
        $repo->expects(self::once())
            ->method('findByListingOwner')
            ->with($user)
            ->willReturn([$report]);

        $service = new ListingReportService(
            $repo,
            $this->createMock(ListingService::class),
            $this->createMock(EntityManagerInterface::class),
        );

        $items = $service->listForSeller($user);

        self::assertCount(1, $items);
        self::assertInstanceOf(ListingReportResponse::class, $items[0]);
        self::assertSame(42, $items[0]->listingId);
        self::assertSame(ListingReportReason::Spam, $items[0]->reason);
        self::assertSame('ул. Тестовая, 1', $items[0]->listingAddress);
    }
}
