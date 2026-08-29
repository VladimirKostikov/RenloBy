<?php

declare(strict_types=1);

namespace App\Tests\DataFixtures;

use PHPUnit\Framework\TestCase;

final class ListingSeedIsTestOnlyTest extends TestCase
{
    public function testFixturesDoNotCreatePublicSeedListingTwins(): void
    {
        $fixtures = (string) file_get_contents(dirname(__DIR__, 2) . '/src/DataFixtures/AppFixtures.php');
        $seeder = (string) file_get_contents(dirname(__DIR__, 2) . '/src/Service/AdminNationwideListingSeeder.php');

        self::assertStringNotContainsString('foreach ([true, false] as $isTest)', $fixtures);
        self::assertStringNotContainsString('foreach ([true, false] as $isTest)', $seeder);
        self::assertStringContainsString('foreach ([true] as $isTest)', $fixtures);
        self::assertStringContainsString('foreach ([true] as $isTest)', $seeder);
    }
}
