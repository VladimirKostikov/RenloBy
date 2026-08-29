<?php

declare(strict_types=1);

namespace App\Tests\DataFixtures;

use App\DataFixtures\UserSeedDefinitions;
use PHPUnit\Framework\TestCase;

final class UserSeedDefinitionsTest extends TestCase
{
    public function testIncludesAdminAndRegularUsers(): void
    {
        $emails = array_map(static fn (array $entry): string => $entry['email'], UserSeedDefinitions::ENTRIES);

        self::assertCount(5, UserSeedDefinitions::ENTRIES);
        self::assertContains('admin@renlo.local', $emails);
        self::assertContains('user@renlo.local', $emails);
        self::assertContains('seller@renlo.local', $emails);

        $admin = UserSeedDefinitions::ENTRIES[0];
        self::assertSame('admin@renlo.local', $admin['email']);
        self::assertContains('ROLE_ADMIN', $admin['roles']);
    }

    public function testSeedUsersHaveRussianFullNamesAndStockPhotos(): void
    {
        foreach (UserSeedDefinitions::ENTRIES as $entry) {
            self::assertMatchesRegularExpression('/^\+375(25|29|33|44)\d{7}$/', $entry['phone']);
            self::assertSame($entry['phone'], $entry['whatsapp']);
            self::assertSame($entry['phone'], $entry['viber']);
            self::assertNotSame('', $entry['lastName']);
            self::assertNotSame('', $entry['firstName']);
            self::assertNotSame('', $entry['patronymic']);
            self::assertMatchesRegularExpression(
                '/^[\p{Cyrillic}\-]+$/u',
                $entry['lastName'] . $entry['firstName'] . $entry['patronymic'],
            );
            self::assertDoesNotMatchRegularExpression('/[іўІЎa-zA-Z]/u', $entry['lastName'] . $entry['firstName'] . $entry['patronymic']);
            self::assertStringContainsString('_demo', $entry['telegram']);
            self::assertStringStartsWith('https://images.unsplash.com/', $entry['photo']);
            self::assertStringContainsString('w=256', $entry['photo']);
        }

        $names = array_map(
            static fn (array $entry): string => $entry['lastName'] . ' ' . $entry['firstName'],
            UserSeedDefinitions::ENTRIES,
        );
        self::assertSame(count($names), count(array_unique($names)));
        self::assertContains('Иванов Андрей', $names);
        self::assertContains('Петров Максим', $names);
        self::assertContains('Морозов Павел', $names);
    }
}
