<?php

declare(strict_types=1);

namespace App\Dto\Article;

use App\Enum\ArticleCategory;

readonly class UpdateArticleRequest
{
    /**
     * @param list<array{url?: mixed, type?: mixed}>|null $media
     */
    public function __construct(
        public ?string $slug = null,
        public ?string $title = null,
        public ?string $excerpt = null,
        public ?string $body = null,
        public ?ArticleCategory $category = null,
        public ?string $coverImage = null,
        public ?bool $isPublished = null,
        public ?string $publishedAt = null,
        public ?string $metaTitle = null,
        public ?string $metaDescription = null,
        public ?bool $isTest = null,
        public ?array $media = null,
    ) {
    }
}
