<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Exception\ValidationException;
use App\Service\CurrencyConverter;
use PHPUnit\Framework\TestCase;

final class CurrencyConverterTest extends TestCase
{
    public function testConvertsAccessibleTariffPrices(): void
    {
        $converter = new CurrencyConverter('3.27', '93');

        self::assertSame('32.00', $converter->fromUsd('9.90', 'BYN'));
        self::assertSame('920.00', $converter->fromUsd('9.90', 'RUB'));
        self::assertSame('9.90', $converter->fromUsd('9.90', 'USD'));
    }

    public function testRejectsUnsupportedCurrency(): void
    {
        $converter = new CurrencyConverter(3.27, 93);

        $this->expectException(ValidationException::class);
        $converter->assertSupported('EUR');
    }
}
