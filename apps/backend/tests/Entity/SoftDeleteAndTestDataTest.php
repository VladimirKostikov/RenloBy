<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\City;
use App\Entity\PaymentTransaction;
use PHPUnit\Framework\TestCase;

final class SoftDeleteAndTestDataTest extends TestCase
{
    public function testCitySoftDeleteAndIsTest(): void
    {
        $city = (new City())->setName('Minsk')->setSlug('minsk')->setRegionSlug('minsk-city')->setIsTest(true);
        self::assertTrue($city->isTest());
        self::assertFalse($city->isDeleted());

        $city->softDelete();
        self::assertTrue($city->isDeleted());
        self::assertInstanceOf(\DateTimeImmutable::class, $city->getDeletedAt());

        $city->restore();
        self::assertFalse($city->isDeleted());
    }

    public function testPaymentTransactionDefaults(): void
    {
        $tx = new PaymentTransaction();
        self::assertFalse($tx->isTest());
        self::assertFalse($tx->isDeleted());
        $tx->setIsTest(true)->softDelete();
        self::assertTrue($tx->isTest());
        self::assertTrue($tx->isDeleted());
    }
}
