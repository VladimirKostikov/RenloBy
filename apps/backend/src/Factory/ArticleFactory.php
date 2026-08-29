<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Article;
use App\Enum\ArticleCategory;

class ArticleFactory
{
    /**
     * @param list<array{url: string, type: string}> $media
     */
    public function create(
        string $slug = 'sample-article',
        string $title = 'Article',
        string $excerpt = '',
        string $body = '',
        ArticleCategory $category = ArticleCategory::Guides,
        ?string $coverImage = null,
        bool $isPublished = true,
        ?\DateTimeImmutable $publishedAt = null,
        ?string $metaTitle = null,
        ?string $metaDescription = null,
        bool $isTest = true,
        array $media = [],
    ): Article {
        $article = (new Article())
            ->setSlug($slug)
            ->setTitle($title)
            ->setExcerpt($excerpt)
            ->setBody($body)
            ->setCategory($category)
            ->setCoverImage($coverImage)
            ->setMedia($media)
            ->setIsPublished($isPublished)
            ->setMetaTitle($metaTitle)
            ->setMetaDescription($metaDescription)
            ->setIsTest($isTest);

        if ($publishedAt instanceof \DateTimeImmutable) {
            $article->setPublishedAt($publishedAt);
        }

        return $article;
    }
}
