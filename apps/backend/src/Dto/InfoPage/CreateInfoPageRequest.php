<?php

declare(strict_types=1);

namespace App\Dto\InfoPage;

use App\Enum\InfoPageCategory;

readonly class CreateInfoPageRequest
{
    public function __construct(
        public string $slug,
        public string $title,
        public string $body,
        public InfoPageCategory $category,
        public ?string $importantNote,
        public array $faqItems,
        public int $sortOrder,
        public ?string $metaTitle = null,
        public ?string $metaDescription = null,
        public ?bool $isTest = null,
    ) {
    }
}
