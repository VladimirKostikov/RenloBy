<?php

declare(strict_types=1);

namespace App\Dto\Article;

use App\Entity\Article;

readonly class ArticleResponse
{
    /**
     * @param list<array{url: string, type: string}> $media
     */
    public function __construct(
        public int $id,
        public string $slug,
        public string $title,
        public string $excerpt,
        public string $body,
        public string $category,
        public ?string $coverImage,
        public array $media,
        public bool $isPublished,
        public string $publishedAt,
        public ?string $metaTitle,
        public ?string $metaDescription,
        public string $updatedAt,
        public bool $isTest,
    ) {
    }

    public static function fromEntity(Article $article): self
    {
        return new self(
            $article->getId() ?? 0,
            $article->getSlug(),
            $article->getTitle(),
            $article->getExcerpt(),
            $article->getBody(),
            $article->getCategory()->value,
            $article->getCoverImage(),
            $article->getMedia(),
            $article->isPublished(),
            $article->getPublishedAt()->format('Y-m-d'),
            $article->getMetaTitle(),
            $article->getMetaDescription(),
            $article->getUpdatedAt()->format('Y-m-d'),
            $article->isTest(),
        );
    }
}
