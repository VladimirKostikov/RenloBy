<?php

declare(strict_types=1);

namespace App\Enum;

enum ListingType: string
{
    case Apartment = 'apartment';
    case House = 'house';
    case Room = 'room';
    case Commercial = 'commercial';
}
