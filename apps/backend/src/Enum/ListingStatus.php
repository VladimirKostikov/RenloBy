<?php

declare(strict_types=1);

namespace App\Enum;

enum ListingStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';
    case Archived = 'archived';
}
