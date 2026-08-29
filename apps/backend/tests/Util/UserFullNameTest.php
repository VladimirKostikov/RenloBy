<?php

declare(strict_types=1);

namespace App\Tests\Util;

use App\Util\UserFullName;
use PHPUnit\Framework\TestCase;

final class UserFullNameTest extends TestCase
{
    public function testComposeJoinsNonEmptyPartsInRussianOrder(): void
    {
        self::assertSame(
            'Иванов Иван Иванович',
            UserFullName::compose('Иванов', 'Иван', 'Иванович'),
        );
        self::assertSame('Иван', UserFullName::compose(null, 'Иван', null));
        self::assertSame('', UserFullName::compose(null, null, null));
        self::assertSame('', UserFullName::compose('  ', '', null));
    }

    public function testNormalizePartTrimsEmptyToNull(): void
    {
        self::assertSame('Иван', UserFullName::normalizePart(' Иван '));
        self::assertNull(UserFullName::normalizePart(''));
        self::assertNull(UserFullName::normalizePart('   '));
        self::assertNull(UserFullName::normalizePart(null));
    }
}
