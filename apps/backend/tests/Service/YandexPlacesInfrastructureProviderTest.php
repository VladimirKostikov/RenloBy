<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\YandexPlacesInfrastructureProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class YandexPlacesInfrastructureProviderTest extends TestCase
{
    public function testIsNotConfiguredWithoutApiKey(): void
    {
        $provider = new YandexPlacesInfrastructureProvider(new MockHttpClient(), '');

        self::assertFalse($provider->isConfigured());
    }

    public function testParsesBizSearchResults(): void
    {
        $payload = json_encode([
            'type' => 'FeatureCollection',
            'features' => [
                [
                    'type' => 'Feature',
                    'properties' => [
                        'name' => 'Аптека №1',
                        'description' => 'Минск',
                        'CompanyMetaData' => [
                            'id' => '12345',
                            'name' => 'Аптека №1',
                            'address' => 'пр. Независимости, 10',
                            'Categories' => [
                                ['class' => 'pharmacy', 'name' => 'Аптека'],
                            ],
                        ],
                    ],
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [27.5615, 53.9045],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $client = new MockHttpClient(static fn (): MockResponse => new MockResponse($payload));
        $provider = new YandexPlacesInfrastructureProvider($client, 'test-key');

        $items = $provider->search(['pharmacy'], 53.88, 27.45, 53.95, 27.70, 14);

        self::assertCount(1, $items);
        self::assertSame('yandex-12345', $items[0]['id']);
        self::assertSame('pharmacy', $items[0]['type']);
        self::assertSame('Аптека №1', $items[0]['name']);
        self::assertSame('пр. Независимости, 10', $items[0]['address']);
        self::assertSame(53.9045, $items[0]['latitude']);
        self::assertSame(27.5615, $items[0]['longitude']);
    }

    public function testFiltersResultsOutsideViewport(): void
    {
        $payload = json_encode([
            'type' => 'FeatureCollection',
            'features' => [
                [
                    'type' => 'Feature',
                    'properties' => [
                        'CompanyMetaData' => [
                            'id' => 'outside',
                            'name' => 'Far pharmacy',
                            'address' => 'Far',
                            'Categories' => [['class' => 'pharmacy', 'name' => 'Аптека']],
                        ],
                    ],
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [24.0, 52.0],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $client = new MockHttpClient(static fn (): MockResponse => new MockResponse($payload));
        $provider = new YandexPlacesInfrastructureProvider($client, 'test-key');

        $items = $provider->search(['pharmacy'], 53.88, 27.45, 53.95, 27.70, 14);

        self::assertSame([], $items);
    }

    public function testFetchesMultipleTypesInParallelRequests(): void
    {
        $payload = json_encode([
            'type' => 'FeatureCollection',
            'features' => [],
        ], JSON_THROW_ON_ERROR);

        $requests = 0;
        $client = new MockHttpClient(static function () use (&$requests, $payload): MockResponse {
            ++$requests;

            return new MockResponse($payload);
        });
        $provider = new YandexPlacesInfrastructureProvider($client, 'test-key');

        $provider->search(['shop', 'pharmacy', 'school', 'park'], 53.88, 27.45, 53.95, 27.70, 14);

        self::assertSame(4, $requests);
    }

    public function testThrowsOnHttpError(): void
    {
        $client = new MockHttpClient(static fn (): MockResponse => new MockResponse('{"message":"Invalid apikey"}', [
            'http_code' => 403,
        ]));
        $provider = new YandexPlacesInfrastructureProvider($client, 'bad-key');

        $this->expectException(\RuntimeException::class);
        $provider->search(['shop'], 53.88, 27.45, 53.95, 27.70, 14);
    }
}
