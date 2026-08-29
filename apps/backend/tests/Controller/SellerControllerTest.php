<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SellerControllerTest extends WebTestCase
{
    public function testGetSellerProfile(): void
    {
        $client = static::createClient();
        $sellerId = $this->findSellerId($client);

        $client->request('GET', '/api/sellers/' . $sellerId);
        self::assertResponseIsSuccessful();

        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($sellerId, $payload['id']);
        self::assertArrayHasKey('name', $payload);
        self::assertArrayHasKey('lastSeenAt', $payload);
        self::assertArrayHasKey('registeredAt', $payload);
        self::assertArrayHasKey('listingsCount', $payload);
        self::assertArrayNotHasKey('email', $payload);
    }

    public function testGetSellerListings(): void
    {
        $client = static::createClient();
        $sellerId = $this->findSellerId($client);

        $client->request('GET', '/api/sellers/' . $sellerId . '/listings?limit=5');
        self::assertResponseIsSuccessful();

        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('items', $payload);
        self::assertIsArray($payload['items']);
        foreach ($payload['items'] as $item) {
            self::assertSame($sellerId, $item['userId']);
            self::assertSame('published', $item['status']);
        }
    }

    public function testGetSellerNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/sellers/99999999');
        self::assertResponseStatusCodeSame(404);
    }

    private function findSellerId($client): int
    {
        $client->request('GET', '/api/listings?limit=1');
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        $items = $data['items'] ?? [];
        self::assertNotEmpty($items);
        self::assertArrayHasKey('userId', $items[0]);

        return (int) $items[0]['userId'];
    }
}
