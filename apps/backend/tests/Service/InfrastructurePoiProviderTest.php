<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\GooglePlacesInfrastructureProvider;
use App\Service\InfrastructurePoiProvider;
use App\Service\OverpassInfrastructureProvider;
use App\Service\YandexPlacesInfrastructureProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class InfrastructurePoiProviderTest extends TestCase
{
    public function testUsesYandexWhenConfigured(): void
    {
        $yandexPayload = json_encode([
            'type' => 'FeatureCollection',
            'features' => [
                [
                    'type' => 'Feature',
                    'properties' => [
                        'CompanyMetaData' => [
                            'id' => 'y1',
                            'name' => 'Евроопт',
                            'address' => 'ул. Ленина, 1',
                            'Categories' => [['class' => 'supermarket', 'name' => 'Супермаркет']],
                        ],
                    ],
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [27.56, 53.9],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $yandex = new YandexPlacesInfrastructureProvider(new MockHttpClient([
            new MockResponse($yandexPayload, ['http_code' => 200]),
        ]), 'yandex-key');
        $google = new GooglePlacesInfrastructureProvider(new MockHttpClient(), '');
        $overpass = new OverpassInfrastructureProvider(new MockHttpClient());
        $provider = new InfrastructurePoiProvider($yandex, $google, $overpass);

        $items = $provider->getForViewport(['shop'], 53.88, 27.45, 53.95, 27.70, 14);

        self::assertCount(1, $items);
        self::assertSame('yandex-y1', $items[0]['id']);
        self::assertSame('Евроопт', $items[0]['name']);
    }

    public function testCachesViewportResults(): void
    {
        $requests = 0;
        $yandexPayload = json_encode([
            'type' => 'FeatureCollection',
            'features' => [
                [
                    'type' => 'Feature',
                    'properties' => [
                        'CompanyMetaData' => [
                            'id' => 'y1',
                            'name' => 'Евроопт',
                            'address' => 'ул. Ленина, 1',
                            'Categories' => [['class' => 'supermarket', 'name' => 'Супермаркет']],
                        ],
                    ],
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [27.56, 53.9],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $yandex = new YandexPlacesInfrastructureProvider(new MockHttpClient(static function () use (&$requests, $yandexPayload): MockResponse {
            ++$requests;

            return new MockResponse($yandexPayload, ['http_code' => 200]);
        }), 'yandex-key');
        $google = new GooglePlacesInfrastructureProvider(new MockHttpClient(), '');
        $overpass = new OverpassInfrastructureProvider(new MockHttpClient());
        $provider = new InfrastructurePoiProvider($yandex, $google, $overpass);

        $first = $provider->getForViewport(['shop'], 53.88, 27.45, 53.95, 27.70, 14);
        $second = $provider->getForViewport(['shop'], 53.88, 27.45, 53.95, 27.70, 14);

        self::assertSame($first, $second);
        self::assertSame(1, $requests);
    }

    public function testUsesOverpassWhenYandexAndGoogleAreNotConfigured(): void
    {
        $payload = json_encode([
            'elements' => [
                [
                    'type' => 'node',
                    'id' => 55,
                    'lat' => 53.9,
                    'lon' => 27.56,
                    'tags' => ['shop' => 'supermarket', 'name' => 'Евроопт'],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $yandex = new YandexPlacesInfrastructureProvider(new MockHttpClient(), '');
        $google = new GooglePlacesInfrastructureProvider(new MockHttpClient(), '');
        $overpass = new OverpassInfrastructureProvider(new MockHttpClient([
            new MockResponse($payload, ['http_code' => 200]),
        ]));
        $provider = new InfrastructurePoiProvider($yandex, $google, $overpass);

        $items = $provider->getForViewport(['shop'], 53.88, 27.45, 53.95, 27.70, 14);

        self::assertCount(1, $items);
        self::assertSame('shop', $items[0]['type']);
        self::assertSame('Евроопт', $items[0]['name']);
    }
}
