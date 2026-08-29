<?php

declare(strict_types=1);

namespace App\Enum;

enum DealType: string
{
    case Sale = 'sale';
    case Rent = 'rent';
}
