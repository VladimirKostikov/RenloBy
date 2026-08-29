<?php

declare(strict_types=1);

namespace App\Enum;

enum RentTerm: string
{
    case Daily = 'daily';
    case Long = 'long';
}
