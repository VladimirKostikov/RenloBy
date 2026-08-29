<?php

declare(strict_types=1);

namespace App\Tests\DataFixtures;

use App\DataFixtures\SeoMetaSeedDefinitions;
use PHPUnit\Framework\TestCase;

final class SeoMetaSeedDefinitionsTest extends TestCase
{
    public function testEntriesHaveRequiredKeys(): void
    {
        $entries = SeoMetaSeedDefinitions::entries();
        self::assertNotEmpty($entries);

        foreach ($entries as $entry) {
            self::assertArrayHasKey('pageKey', $entry);
            self::assertArrayHasKey('locale', $entry);
            self::assertArrayHasKey('title', $entry);
            self::assertArrayHasKey('description', $entry);
            self::assertContains($entry['locale'], ['ru', 'en']);
        }
    }
}
