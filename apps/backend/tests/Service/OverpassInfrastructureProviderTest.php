<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\OverpassInfrastructureProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class OverpassInfrastructureProviderTest extends TestCase
{
    public function testMapsOverpassElementsToPois(): void
    {
        $payload = json_encode([
            'elements' => [
                [
                    'type' => 'node',
                    'id' => 101,
                    'lat' => 53.9,
                    'lon' => 27.56,
                    'tags' => [
                        'amenity' => 'pharmacy',
                        'name' => 'Белфармация',
                        'addr:street' => 'пр. Независимости',
                        'addr:housenumber' => '10',
                    ],
                ],
                [
                    'type' => 'way',
                    'id' => 202,
                    'center' => ['lat' => 53.91, 'lon' => 27.57],
                    'tags' => [
                        'leisure' => 'park',
                        'name' => 'Парк',
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $client = new MockHttpClient([
            new MockResponse($payload, ['http_code' => 200]),
        ]);

        $provider = new OverpassInfrastructureProvider($client);
        $items = $provider->search(['pharmacy', 'park'], 53.88, 27.45, 53.95, 27.70, 14);

        self::assertCount(2, $items);
        self::assertSame('pharmacy-node-101', $items[0]['id']);
        self::assertSame('pharmacy', $items[0]['type']);
        self::assertSame('Белфармация', $items[0]['name']);
        self::assertSame('пр. Независимости, 10', $items[0]['address']);
        self::assertSame('park-way-202', $items[1]['id']);
        self::assertSame('park', $items[1]['type']);
    }
}
