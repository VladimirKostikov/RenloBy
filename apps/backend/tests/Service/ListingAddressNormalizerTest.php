<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\ListingAddressNormalizer;
use PHPUnit\Framework\TestCase;

final class ListingAddressNormalizerTest extends TestCase
{
    public function testExtractsStreetBeforeHouseNumber(): void
    {
        $normalizer = new ListingAddressNormalizer();

        self::assertSame('пр. Независимости', $normalizer->streetFromAddress('пр. Независимости, 25'));
        self::assertSame('ул. Ленина', $normalizer->streetFromAddress('ул. Ленина, 10'));
        self::assertSame('пр. Победителей', $normalizer->streetFromAddress('пр. Победителей'));
    }
}
