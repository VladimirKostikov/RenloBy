<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MeControllerTest extends WebTestCase
{
    private const PASSWORD = 'SecurePass1';

    public function testSummaryRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/me/summary');

        self::assertResponseStatusCodeSame(401);
    }

    public function testSummaryReturnsCounts(): void
    {
        $client = static::createClient();
        $this->registerUser($client, 'me-summary@donmap.local');

        $client->request('GET', '/api/me/summary');
        self::assertResponseIsSuccessful();

        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('listingsCount', $payload);
        self::assertArrayHasKey('favoritesCount', $payload);
        self::assertArrayHasKey('comparisonsCount', $payload);
        self::assertArrayHasKey('savedSearchesCount', $payload);
    }

    public function testListingsReturnsOnlyCurrentUserListings(): void
    {
        $client = static::createClient();
        $this->registerUser($client, 'me-listings@donmap.local');

        $client->request('GET', '/api/me/listings');
        self::assertResponseIsSuccessful();

        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('items', $payload);
        self::assertArrayHasKey('total', $payload);
        self::assertSame(1, $payload['page']);
        self::assertSame(20, $payload['limit']);
        self::assertLessThanOrEqual(20, count($payload['items']));
    }

    public function testListingsAcceptsPageAndLimit(): void
    {
        $client = static::createClient();
        $this->registerUser($client, 'me-listings-page@donmap.local');

        $client->request('GET', '/api/me/listings?page=1&limit=20');
        self::assertResponseIsSuccessful();

        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(1, $payload['page']);
        self::assertSame(20, $payload['limit']);
    }

    public function testUpdateProfileChangesNameParts(): void
    {
        $client = static::createClient();
        $this->registerUser($client, 'me-profile@donmap.local');

        $client->request('GET', '/api/auth/me');
        self::assertResponseIsSuccessful();
        $me = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('', $me['name']);
        self::assertNull($me['lastName']);
        self::assertNull($me['firstName']);
        self::assertNull($me['patronymic']);

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
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();

        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Иванов Иван Иванович', $payload['name']);
        self::assertSame('Иванов', $payload['lastName']);
        self::assertSame('Иван', $payload['firstName']);
        self::assertSame('Иванович', $payload['patronymic']);

        $client->request('GET', '/api/auth/me');
        $meAgain = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Иванов Иван Иванович', $meAgain['name']);
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
}
