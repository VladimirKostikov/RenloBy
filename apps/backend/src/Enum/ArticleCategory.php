<?php

declare(strict_types=1);

namespace App\Enum;

enum ArticleCategory: string
{
    case Guides = 'guides';
    case Market = 'market';
    case Tips = 'tips';
    case Law = 'law';
}
