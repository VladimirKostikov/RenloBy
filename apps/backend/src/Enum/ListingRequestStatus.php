<?php

declare(strict_types=1);

namespace App\Enum;

enum ListingRequestStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Closed = 'closed';
}
