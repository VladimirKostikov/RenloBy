<?php

declare(strict_types=1);

namespace App\Tests\DataFixtures;

use App\DataFixtures\SiteSettingsSeedDefinitions;
use PHPUnit\Framework\TestCase;

final class SiteSettingsSeedDefinitionsTest extends TestCase
{
    public function testDefaultsContainContacts(): void
    {
        $defaults = SiteSettingsSeedDefinitions::defaults();
        self::assertNotSame('', $defaults['aboutText']);
        self::assertNotSame('', $defaults['phoneDisplay']);
        self::assertNotSame('', $defaults['email']);
        self::assertNotSame('', $defaults['offersEmail']);
        self::assertStringStartsWith('https://', $defaults['telegramUrl']);
        self::assertStringStartsWith('https://', $defaults['whatsappUrl']);
        self::assertStringStartsWith('https://', $defaults['vkUrl']);
    }
}
