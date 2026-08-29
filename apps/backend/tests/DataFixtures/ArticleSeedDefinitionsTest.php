<?php

declare(strict_types=1);

namespace App\Tests\DataFixtures;

use App\DataFixtures\ArticleSeedDefinitions;
use App\Enum\ArticleCategory;
use PHPUnit\Framework\TestCase;

final class ArticleSeedDefinitionsTest extends TestCase
{
    public function testArticlesHaveUniqueSlugsAndRequiredFields(): void
    {
        $articles = ArticleSeedDefinitions::articles();
        self::assertGreaterThanOrEqual(10, count($articles));

        $slugs = array_map(static fn (array $item): string => $item['slug'], $articles);
        self::assertSame(count($slugs), count(array_unique($slugs)));

        foreach ($articles as $article) {
            self::assertNotSame('', $article['slug']);
            self::assertNotSame('', $article['title']);
            self::assertNotSame('', $article['excerpt']);
            self::assertNotSame('', $article['body']);
            self::assertInstanceOf(ArticleCategory::class, $article['category']);
            self::assertNotSame('', $article['metaTitle']);
            self::assertNotSame('', $article['metaDescription']);
            self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $article['publishedAt']);
        }
    }
}
