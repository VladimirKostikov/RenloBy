<?php

declare(strict_types=1);

namespace App\Enum;

enum InfoPageCategory: string
{
    case Buyers = 'buyers';
    case Sellers = 'sellers';
    case Renters = 'renters';
    case DealSafety = 'deal_safety';
    case Faq = 'faq';
    case Support = 'support';
    case Offer = 'offer';
    case Privacy = 'privacy';
    case PersonalData = 'personal_data';
}
