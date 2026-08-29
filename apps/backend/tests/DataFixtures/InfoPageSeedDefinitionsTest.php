<?php

declare(strict_types=1);

namespace App\Tests\DataFixtures;

use App\DataFixtures\InfoPageSeedDefinitions;
use App\Enum\InfoPageCategory;
use PHPUnit\Framework\TestCase;

final class InfoPageSeedDefinitionsTest extends TestCase
{
    public function testIncludesAllPublicInfoSlugs(): void
    {
        $slugs = array_map(static fn (array $page): string => $page['slug'], InfoPageSeedDefinitions::pages());

        self::assertSame(
            [
                'buyers',
                'sellers',
                'renters',
                'deal-safety',
                'faq',
                'support',
                'offer',
                'privacy',
                'personal-data',
            ],
            $slugs,
        );
    }

    public function testLegalPagesHaveRequiredContent(): void
    {
        $pages = InfoPageSeedDefinitions::pages();
        $bySlug = [];
        foreach ($pages as $page) {
            $bySlug[$page['slug']] = $page;
        }

        self::assertSame(InfoPageCategory::Offer, $bySlug['offer']['category']);
        self::assertSame(InfoPageCategory::Privacy, $bySlug['privacy']['category']);
        self::assertSame(InfoPageCategory::PersonalData, $bySlug['personal-data']['category']);
        self::assertStringContainsString('## 1. Общие положения', $bySlug['offer']['body']);
        self::assertStringContainsString('## 1. Какие данные мы обрабатываем', $bySlug['privacy']['body']);
        self::assertStringContainsString('## 1. Предмет соглашения', $bySlug['personal-data']['body']);
    }

    public function testDealSafetyPageHasGuideContent(): void
    {
        $dealSafety = InfoPageSeedDefinitions::pages()[3];

        self::assertSame('deal-safety', $dealSafety['slug']);
        self::assertSame(InfoPageCategory::DealSafety, $dealSafety['category']);
        self::assertStringContainsString('## Перед встречей', $dealSafety['body']);
        self::assertNotEmpty($dealSafety['importantNote']);
        self::assertCount(5, $dealSafety['faqItems']);
    }

    public function testFaqPageHasItems(): void
    {
        $faq = InfoPageSeedDefinitions::pages()[4];

        self::assertSame('faq', $faq['slug']);
        self::assertGreaterThanOrEqual(5, count($faq['faqItems']));
        self::assertStringNotContainsString('Lorem ipsum', $faq['body']);
    }
}
