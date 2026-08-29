<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ListingAdminModerationTest extends WebTestCase
{
    private const ADMIN_EMAIL = 'admin@renlo.local';
    private const ADMIN_PASSWORD = 'Admin123!';
    private const PASSWORD = 'SecurePass1';

    public function testAdminCanApprovePendingListing(): void
    {
        $client = static::createClient();
        $this->registerUser($client, 'moderation-seller@donmap.local');
        $this->completeSellerProfile($client);

        $client->request(
            'POST',
            '/api/me/listings',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'dealType' => 'sale',
                'listingType' => 'apartment',
                'price' => 150000,
                'rooms' => 2,
                'area' => 60,
                'floor' => 3,
                'totalFloors' => 9,
                'address' => 'ул. Модерация, 5',
                'latitude' => 53.9,
                'longitude' => 27.56,
                'city' => 'Минск',
                'district' => 'Центр',
                'images' => ['https://example.com/pending.jpg'],
                'status' => 'published',
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('pending', $created['status']);
        $listingId = $created['id'];

        $this->loginAsAdmin($client);

        $client->request(
            'GET',
            '/admin/listings',
            ['status' => 'pending', 'limit' => 100],
            [],
            ['HTTP_X_ADMIN_TEST_MODE' => '0'],
        );
        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        $ids = array_map(static fn (array $item): int => (int) $item['id'], $list['items'] ?? []);
        self::assertContains($listingId, $ids);

        $client->request(
            'PATCH',
            '/admin/listings/' . $listingId,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_ADMIN_TEST_MODE' => '0',
            ],
            json_encode(['status' => 'published'], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
        $updated = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('published', $updated['status']);
    }

    public function testAdminCanRejectListingAndUpdateImages(): void
    {
        $client = static::createClient();
        $this->registerUser($client, 'reject-seller@donmap.local');
        $this->completeSellerProfile($client);

        $client->request(
            'POST',
            '/api/me/listings',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'dealType' => 'sale',
                'listingType' => 'apartment',
                'price' => 120000,
                'rooms' => 1,
                'area' => 40,
                'floor' => 2,
                'totalFloors' => 5,
                'address' => 'ул. Отказ, 1',
                'latitude' => 53.91,
                'longitude' => 27.55,
                'city' => 'Минск',
                'district' => 'Центр',
                'images' => ['https://example.com/old.jpg'],
                'status' => 'published',
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        $listingId = $created['id'];

        $this->loginAsAdmin($client);

        $client->request(
            'PATCH',
            '/admin/listings/' . $listingId,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_ADMIN_TEST_MODE' => '0',
            ],
            json_encode([
                'status' => 'rejected',
                'images' => [
                    '/uploads/listings/2026/07/photo.jpg',
                    'javascript:alert(1)',
                    'https://cdn.example.com/ok.jpg',
                ],
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
        $updated = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('rejected', $updated['status']);
        self::assertSame(
            ['/uploads/listings/2026/07/photo.jpg', 'https://cdn.example.com/ok.jpg'],
            $updated['images'],
        );
    }

    public function testAdminListingsRequireAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/listings', ['status' => 'pending']);
        self::assertResponseStatusCodeSame(401);
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

    private function completeSellerProfile($client): void
    {
        $client->request(
            'PATCH',
            '/api/auth/me',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'lastName' => 'Иванов',
                'firstName' => 'Иван',
                'patronymic' => 'Иванович',
                'telegram' => '@seller',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
    }
}
