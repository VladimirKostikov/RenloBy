<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\InfoPage;
use App\Enum\InfoPageCategory;

class InfoPageFactory
{
    public function create(
        string $slug = 'deal-safety',
        string $title = 'Info page',
        string $body = '',
        InfoPageCategory $category = InfoPageCategory::DealSafety,
        ?string $importantNote = null,
        array $faqItems = [],
        int $sortOrder = 0,
        bool $isTest = true,
    ): InfoPage {
        return (new InfoPage())
            ->setSlug($slug)
            ->setTitle($title)
            ->setBody($body)
            ->setCategory($category)
            ->setImportantNote($importantNote)
            ->setFaqItems($faqItems)
            ->setSortOrder($sortOrder)
            ->setIsTest($isTest);
    }
}
