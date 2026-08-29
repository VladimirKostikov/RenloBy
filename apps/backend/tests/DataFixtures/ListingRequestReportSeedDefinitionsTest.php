<?php

declare(strict_types=1);

namespace App\Tests\DataFixtures;

use App\DataFixtures\ListingReportSeedDefinitions;
use App\DataFixtures\ListingRequestSeedDefinitions;
use App\Enum\ListingReportReason;
use App\Enum\ListingReportStatus;
use App\Enum\ListingRequestStatus;
use PHPUnit\Framework\TestCase;

final class ListingRequestReportSeedDefinitionsTest extends TestCase
{
    public function testRequestSeedsHaveValidStatusesAndPhones(): void
    {
        $rows = ListingRequestSeedDefinitions::all();
        self::assertNotEmpty($rows);

        foreach ($rows as $row) {
            self::assertNotSame('', $row['phone']);
            self::assertNotSame('', $row['message']);
            self::assertNotNull(ListingRequestStatus::tryFrom($row['status']));
            if ($row['requesterEmail'] !== null) {
                self::assertStringContainsString('@renlo.local', $row['requesterEmail']);
            }
        }

        $statuses = array_column($rows, 'status');
        self::assertContains('new', $statuses);
        self::assertContains('contacted', $statuses);
        self::assertContains('closed', $statuses);
    }

    public function testReportSeedsHaveValidReasonsAndStatuses(): void
    {
        $rows = ListingReportSeedDefinitions::all();
        self::assertNotEmpty($rows);

        foreach ($rows as $row) {
            self::assertNotNull(ListingReportReason::tryFrom($row['reason']));
            self::assertNotNull(ListingReportStatus::tryFrom($row['status']));
        }

        $reasons = array_column($rows, 'reason');
        self::assertContains('spam', $reasons);
        self::assertContains('fraud', $reasons);
        self::assertContains('wrong', $reasons);
    }
}
