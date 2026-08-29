<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ListingMarketControllerTest extends WebTestCase
{
    public function testMarketEndpointReturnsSnapshot(): void
    {
        $client = static::createClient();
        $listingId = $this->findListingId($client);

        $client->request('GET', '/api/listings/' . $listingId . '/market');

        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent() ?: '[]', true, 512, JSON_THROW_ON_ERROR);
        self::assertContains($payload['metric'], ['price', 'price_per_sqm']);
        self::assertArrayHasKey('current', $payload);
        self::assertArrayHasKey('avg', $payload);
        self::assertArrayHasKey('min', $payload);
        self::assertArrayHasKey('max', $payload);
        self::assertArrayHasKey('rank', $payload);
        self::assertArrayHasKey('similarCount', $payload);
        self::assertArrayHasKey('changePct', $payload);
        self::assertArrayHasKey('aiGoodPrice', $payload);
        self::assertIsInt($payload['current']);
        self::assertGreaterThanOrEqual(0, $payload['similarCount']);
    }

    public function testMarketEndpointReturns404ForMissingListing(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/listings/999999991/market');
        self::assertResponseStatusCodeSame(404);
    }

    private function findListingId(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): int
    {
        $client->request('GET', '/api/listings', ['limit' => 1]);
        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertNotEmpty($payload['items']);

        return (int) $payload['items'][0]['id'];
    }
}
