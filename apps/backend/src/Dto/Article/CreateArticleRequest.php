<?php

declare(strict_types=1);

namespace App\Dto\Article;

use App\Enum\ArticleCategory;

readonly class CreateArticleRequest
{
    /**
     * @param list<array{url?: mixed, type?: mixed}>|null $media
     */
    public function __construct(
        public string $slug,
        public string $title,
        public string $excerpt,
        public string $body,
        public ArticleCategory $category,
        public ?string $coverImage,
        public bool $isPublished,
        public string $publishedAt,
        public ?string $metaTitle = null,
        public ?string $metaDescription = null,
        public ?bool $isTest = null,
        public ?array $media = null,
    ) {
    }
}
