<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\GooglePlacesInfrastructureProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class GooglePlacesInfrastructureProviderTest extends TestCase
{
    public function testIsNotConfiguredWithoutApiKey(): void
    {
        $provider = new GooglePlacesInfrastructureProvider(new MockHttpClient(), '');

        self::assertFalse($provider->isConfigured());
    }

    public function testParsesNearbySearchResults(): void
    {
        $payload = json_encode([
            'status' => 'OK',
            'results' => [
                [
                    'place_id' => 'ChIJtest',
                    'name' => 'Аптека №1',
                    'vicinity' => 'пр. Независимости, 10',
                    'geometry' => ['location' => ['lat' => 53.9045, 'lng' => 27.5615]],
                    'types' => ['pharmacy', 'health', 'point_of_interest'],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $client = new MockHttpClient(static fn (): MockResponse => new MockResponse($payload));
        $provider = new GooglePlacesInfrastructureProvider($client, 'test-key');

        $items = $provider->search(['pharmacy'], 53.88, 27.45, 53.95, 27.70, 14);

        self::assertCount(1, $items);
        self::assertSame('google-ChIJtest', $items[0]['id']);
        self::assertSame('pharmacy', $items[0]['type']);
        self::assertSame('Аптека №1', $items[0]['name']);
        self::assertSame('пр. Независимости, 10', $items[0]['address']);
    }

    public function testFiltersResultsOutsideViewport(): void
    {
        $payload = json_encode([
            'status' => 'OK',
            'results' => [
                [
                    'place_id' => 'outside',
                    'name' => 'Far pharmacy',
                    'vicinity' => 'Far away',
                    'geometry' => ['location' => ['lat' => 52.0, 'lng' => 24.0]],
                    'types' => ['pharmacy'],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $client = new MockHttpClient(static fn (): MockResponse => new MockResponse($payload));
        $provider = new GooglePlacesInfrastructureProvider($client, 'test-key');

        $items = $provider->search(['pharmacy'], 53.88, 27.45, 53.95, 27.70, 14);

        self::assertSame([], $items);
    }

    public function testThrowsOnGoogleErrorStatus(): void
    {
        $payload = json_encode(['status' => 'REQUEST_DENIED'], JSON_THROW_ON_ERROR);
        $client = new MockHttpClient(static fn (): MockResponse => new MockResponse($payload));
        $provider = new GooglePlacesInfrastructureProvider($client, 'test-key');

        $this->expectException(\RuntimeException::class);
        $provider->search(['shop'], 53.88, 27.45, 53.95, 27.70, 14);
    }
}
