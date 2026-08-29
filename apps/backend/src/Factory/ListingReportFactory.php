<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Listing;
use App\Entity\ListingReport;
use App\Enum\ListingReportReason;
use App\Enum\ListingReportStatus;

class ListingReportFactory
{
    public function create(
        Listing $listing,
        ListingReportReason $reason = ListingReportReason::Spam,
        ?string $comment = null,
        ListingReportStatus $status = ListingReportStatus::New,
        bool $isTest = true,
    ): ListingReport {
        return (new ListingReport())
            ->setListing($listing)
            ->setReason($reason)
            ->setComment($comment)
            ->setStatus($status)
            ->setIsTest($isTest);
    }
}
