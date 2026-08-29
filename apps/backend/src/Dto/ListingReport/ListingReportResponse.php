<?php

declare(strict_types=1);

namespace App\Dto\ListingReport;

use App\Entity\ListingReport;
use App\Enum\ListingReportReason;
use App\Enum\ListingReportStatus;

readonly class ListingReportResponse
{
    public function __construct(
        public int $id,
        public int $listingId,
        public ListingReportReason $reason,
        public ?string $comment,
        public ListingReportStatus $status,
        public string $createdAt,
        public bool $isTest,
        public ?string $listingAddress = null,
    ) {
    }

    public static function fromEntity(ListingReport $report): self
    {
        return new self(
            $report->getId() ?? 0,
            $report->getListing()?->getId() ?? 0,
            $report->getReason(),
            $report->getComment(),
            $report->getStatus(),
            $report->getCreatedAt()->format(\DateTimeInterface::ATOM),
            $report->isTest(),
            $report->getListing()?->getAddress(),
        );
    }
}
