<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\InfrastructurePoiGenerator;
use PHPUnit\Framework\TestCase;

final class InfrastructurePoiGeneratorTest extends TestCase
{
    public function testGeneratesPoisAtListingCoordinates(): void
    {
        $generator = new InfrastructurePoiGenerator();

        $items = $generator->generate(
            ['shop'],
            [
                [
                    'id' => 10,
                    'latitude' => 53.9045,
                    'longitude' => 27.5615,
                    'address' => 'ул. Ленина, 10',
                ],
            ],
            53.88,
            27.45,
            53.95,
            27.70,
            14,
        );

        self::assertCount(1, $items);
        self::assertSame('ул. Ленина, 10', $items[0]['address']);
        self::assertEqualsWithDelta(53.9045, $items[0]['latitude'], 0.0001);
        self::assertEqualsWithDelta(27.5615, $items[0]['longitude'], 0.0001);
    }

    public function testSkipsListingsWithoutAddress(): void
    {
        $generator = new InfrastructurePoiGenerator();

        $items = $generator->generate(
            ['shop'],
            [
                [
                    'id' => 11,
                    'latitude' => 53.9045,
                    'longitude' => 27.5615,
                    'address' => '',
                ],
            ],
            53.88,
            27.45,
            53.95,
            27.70,
            14,
        );

        self::assertSame([], $items);
    }
}
