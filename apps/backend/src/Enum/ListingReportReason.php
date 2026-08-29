<?php

declare(strict_types=1);

namespace App\Enum;

enum ListingReportReason: string
{
    case Spam = 'spam';
    case Wrong = 'wrong';
    case Fraud = 'fraud';
    case Other = 'other';
}
