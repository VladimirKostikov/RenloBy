<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class FavoriteControllerTest extends WebTestCase
{
    private const PASSWORD = 'SecurePass1';

    public function testGuestCanAccessFavoritesAndReceiveSessionCookie(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/favorites');

        self::assertResponseIsSuccessful();
        self::assertNotNull($client->getResponse()->headers->getCookies()[0] ?? null);
    }

    public function testGuestToggleFavoriteAddsAndRemoves(): void
    {
        $client = static::createClient();
        $listingId = $this->findListingId($client);

        $client->request(
            'POST',
            '/api/favorites/toggle',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['listingId' => $listingId], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
        $added = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($added['active']);
        self::assertNull($added['item']['userId']);
        self::assertSame($listingId, $added['item']['listingId']);

        $client->request(
            'POST',
            '/api/favorites/toggle',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['listingId' => $listingId], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
        $removed = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($removed['active']);

        $client->request(
            'POST',
            '/api/favorites/toggle',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['listingId' => $listingId], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
        $readded = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($readded['active']);
        self::assertSame($listingId, $readded['item']['listingId']);
    }

    public function testToggleFavoriteAddsAndRemovesForAuthenticatedUser(): void
    {
        $client = static::createClient();
        $this->registerUser($client, 'favorites-toggle@renlo.local');
        $listingId = $this->findListingId($client);

        $client->request(
            'POST',
            '/api/favorites/toggle',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['listingId' => $listingId], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
        $added = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($added['active']);
        self::assertSame($listingId, $added['item']['listingId']);

        $client->request(
            'POST',
            '/api/favorites/toggle',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['listingId' => $listingId], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
        $removed = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($removed['active']);

        $client->request(
            'POST',
            '/api/favorites/toggle',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['listingId' => $listingId], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
        $readded = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($readded['active']);
        self::assertSame($listingId, $readded['item']['listingId']);
    }

    public function testFavoritesIndexReturnsItemsWithListings(): void
    {
        $client = static::createClient();
        $this->registerUser($client, 'favorites-index@renlo.local');
        $listingId = $this->findListingId($client);

        $client->request(
            'POST',
            '/api/favorites/toggle',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['listingId' => $listingId], JSON_THROW_ON_ERROR),
        );

        $client->request('GET', '/api/favorites');
        self::assertResponseIsSuccessful();

        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $payload['items']);
        self::assertSame($listingId, $payload['items'][0]['listingId']);
        self::assertSame($listingId, $payload['items'][0]['listing']['id']);
    }

    public function testGuestFavoritesMergeOnRegister(): void
    {
        $client = static::createClient();
        $listingId = $this->findListingId($client);

        $client->request(
            'POST',
            '/api/favorites/toggle',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['listingId' => $listingId], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();

        $this->registerUser($client, 'favorites-merge@renlo.local');

        $client->request('GET', '/api/favorites');
        self::assertResponseIsSuccessful();

        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $payload['items']);
        self::assertSame($listingId, $payload['items'][0]['listingId']);
        self::assertNotNull($payload['items'][0]['userId']);
    }

    private function registerUser($client, string $email): void
    {
        $uniqueEmail = str_replace('@', '+' . uniqid('', true) . '@', $email);

        $client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $uniqueEmail,
                'password' => self::PASSWORD,
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(201);
    }

    private function findListingId($client): int
    {
        $client->request('GET', '/api/listings?limit=1');
        self::assertResponseIsSuccessful();

        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertNotEmpty($payload['items']);

        return (int) $payload['items'][0]['id'];
    }
}
