<?php

declare(strict_types=1);

namespace App\Dto\InfoPage;

use App\Enum\InfoPageCategory;

readonly class UpdateInfoPageRequest
{
    public function __construct(
        public ?string $slug = null,
        public ?string $title = null,
        public ?string $body = null,
        public ?InfoPageCategory $category = null,
        public ?string $importantNote = null,
        public ?array $faqItems = null,
        public ?int $sortOrder = null,
        public ?string $metaTitle = null,
        public ?string $metaDescription = null,
        public ?bool $isTest = null,
    ) {
    }
}
