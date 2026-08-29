<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\SeoMeta;

class SeoMetaFactory
{
    public function create(
        string $pageKey = 'home',
        string $locale = 'ru',
        string $title = 'Title',
        string $description = 'Description',
        ?string $h1 = null,
        ?string $keywords = null,
        bool $isTest = true,
    ): SeoMeta {
        return (new SeoMeta())
            ->setPageKey($pageKey)
            ->setLocale($locale)
            ->setTitle($title)
            ->setDescription($description)
            ->setH1($h1)
            ->setKeywords($keywords)
            ->setIsTest($isTest);
    }
}
