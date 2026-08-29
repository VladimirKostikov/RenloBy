<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ListingRequestControllerTest extends WebTestCase
{
    private const ADMIN_EMAIL = 'admin@renlo.local';
    private const ADMIN_PASSWORD = 'Admin123!';
    private const VALID_MESSAGE = 'Здравствуйте, хочу посмотреть квартиру на выходных.';
    private const VALID_PHONE = '+375 29 111-22-33';

    public function testCreateRequestForListing(): void
    {
        $client = static::createClient();
        $listingId = $this->findListingId($client);

        $client->request(
            'POST',
            '/api/listings/' . $listingId . '/requests',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'phone' => self::VALID_PHONE,
                'message' => self::VALID_MESSAGE,
                'name' => 'Иван',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(201);
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($listingId, $payload['listingId']);
        self::assertSame(self::VALID_PHONE, $payload['phone']);
        self::assertSame(self::VALID_MESSAGE, $payload['message']);
        self::assertSame('Иван', $payload['name']);
        self::assertSame('new', $payload['status']);
    }

    public function testCreateRequestRejectsShortMessage(): void
    {
        $client = static::createClient();
        $listingId = $this->findListingId($client);

        $client->request(
            'POST',
            '/api/listings/' . $listingId . '/requests',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'phone' => self::VALID_PHONE,
                'message' => 'Коротко',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testCreateRequestRejectsInvalidPhone(): void
    {
        $client = static::createClient();
        $listingId = $this->findListingId($client);

        $client->request(
            'POST',
            '/api/listings/' . $listingId . '/requests',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'phone' => 'abc',
                'message' => self::VALID_MESSAGE,
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testAdminListingRequestsRequireAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/listing-requests');
        self::assertResponseStatusCodeSame(401);
    }

    public function testAdminCanListAndUpdateRequest(): void
    {
        $client = static::createClient();
        $listingId = $this->findListingId($client);

        $client->request(
            'POST',
            '/api/listings/' . $listingId . '/requests',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'phone' => self::VALID_PHONE,
                'message' => self::VALID_MESSAGE,
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        $requestId = (int) $created['id'];

        $this->loginAsAdmin($client);
        $client->request('GET', '/admin/listing-requests');
        self::assertResponseIsSuccessful();

        $client->request(
            'PATCH',
            '/admin/listing-requests/' . $requestId,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['status' => 'contacted'], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
        $updated = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('contacted', $updated['status']);
    }

    private function findListingId($client): int
    {
        $client->request('GET', '/api/listings?limit=1');
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        $items = $data['items'] ?? $data;
        self::assertNotEmpty($items);

        return (int) $items[0]['id'];
    }

    private function loginAsAdmin($client): void
    {
        $client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => self::ADMIN_EMAIL,
                'password' => self::ADMIN_PASSWORD,
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
    }
}
