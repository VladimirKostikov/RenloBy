<?php

declare(strict_types=1);

namespace App\Dto\ListingReport;

use App\Enum\ListingReportStatus;

readonly class UpdateListingReportRequest
{
    public function __construct(
        public ?ListingReportStatus $status = null,
    ) {
    }
}
