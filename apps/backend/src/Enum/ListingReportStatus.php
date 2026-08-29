<?php

declare(strict_types=1);

namespace App\Enum;

enum ListingReportStatus: string
{
    case New = 'new';
    case Reviewed = 'reviewed';
    case Closed = 'closed';
}
