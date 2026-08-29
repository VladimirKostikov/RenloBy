<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ListingReportControllerTest extends WebTestCase
{
    private const ADMIN_EMAIL = 'admin@renlo.local';
    private const ADMIN_PASSWORD = 'Admin123!';
    private const VALID_COMMENT = 'Объявление содержит недостоверные данные о цене и адресе.';

    public function testCreateReportForListing(): void
    {
        $client = static::createClient();
        $listingId = $this->findListingId($client);

        $client->request(
            'POST',
            '/api/listings/' . $listingId . '/reports',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'reason' => 'spam',
                'comment' => self::VALID_COMMENT,
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(201);
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($listingId, $payload['listingId']);
        self::assertSame('spam', $payload['reason']);
        self::assertSame('new', $payload['status']);
        self::assertSame(self::VALID_COMMENT, $payload['comment']);
    }

    public function testCreateReportRejectsInvalidReason(): void
    {
        $client = static::createClient();
        $listingId = $this->findListingId($client);

        $client->request(
            'POST',
            '/api/listings/' . $listingId . '/reports',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'reason' => 'not-a-reason',
                'comment' => self::VALID_COMMENT,
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testCreateReportRejectsShortComment(): void
    {
        $client = static::createClient();
        $listingId = $this->findListingId($client);

        $client->request(
            'POST',
            '/api/listings/' . $listingId . '/reports',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'reason' => 'spam',
                'comment' => 'Слишком коротко',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testAdminListingReportsRequireAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/listing-reports');
        self::assertResponseStatusCodeSame(401);
    }

    public function testAdminCanListAndUpdateReport(): void
    {
        $client = static::createClient();
        $listingId = $this->findListingId($client);

        $client->request(
            'POST',
            '/api/listings/' . $listingId . '/reports',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'reason' => 'fraud',
                'comment' => self::VALID_COMMENT,
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        $reportId = (int) $created['id'];

        $this->loginAsAdmin($client);
        $client->request('GET', '/admin/listing-reports');
        self::assertResponseIsSuccessful();

        $client->request(
            'PATCH',
            '/admin/listing-reports/' . $reportId,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['status' => 'reviewed'], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
        $updated = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('reviewed', $updated['status']);
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
