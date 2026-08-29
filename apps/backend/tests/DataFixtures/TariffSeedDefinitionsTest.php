<?php

declare(strict_types=1);

namespace App\Tests\DataFixtures;

use App\DataFixtures\TariffSeedDefinitions;
use PHPUnit\Framework\TestCase;

final class TariffSeedDefinitionsTest extends TestCase
{
    public function testSeedsThreeKnownTariffs(): void
    {
        $codes = array_column(TariffSeedDefinitions::all(), 'code');
        self::assertSame(['basic', 'standard', 'premium'], $codes);

        $standard = TariffSeedDefinitions::all()[1];
        self::assertTrue($standard['isPopular']);
        self::assertSame('19.90', $standard['priceUsd']);
        self::assertSame('65.00', $standard['priceByn']);
        self::assertSame('1850.00', $standard['priceRub']);
    }
}
