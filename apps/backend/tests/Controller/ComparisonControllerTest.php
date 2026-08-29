<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ComparisonControllerTest extends WebTestCase
{
    private const PASSWORD = 'SecurePass1';

    public function testGuestCanAccessComparisonsAndReceiveSessionCookie(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/comparisons');

        self::assertResponseIsSuccessful();
        self::assertNotNull($client->getResponse()->headers->getCookies()[0] ?? null);
    }

    public function testGuestToggleComparisonAddsAndRemoves(): void
    {
        $client = static::createClient();
        $listingId = $this->findListingId($client);

        $client->request(
            'POST',
            '/api/comparisons/toggle',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['listingId' => $listingId], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
        $added = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($added['active']);
        self::assertNull($added['item']['userId']);

        $client->request(
            'POST',
            '/api/comparisons/toggle',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['listingId' => $listingId], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
        $removed = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($removed['active']);
    }

    public function testToggleComparisonAddsAndRemovesForAuthenticatedUser(): void
    {
        $client = static::createClient();
        $this->registerUser($client, 'comparison-toggle@renlo.local');
        $listingId = $this->findListingId($client);

        $client->request(
            'POST',
            '/api/comparisons/toggle',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['listingId' => $listingId], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
        $added = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($added['active']);

        $client->request(
            'POST',
            '/api/comparisons/toggle',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['listingId' => $listingId], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
        $removed = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($removed['active']);
    }

    public function testComparisonLimitReturnsValidationError(): void
    {
        $client = static::createClient();
        $this->registerUser($client, 'comparison-limit@renlo.local');

        $client->request('GET', '/api/listings?limit=5');
        self::assertResponseIsSuccessful();
        $listings = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR)['items'];
        if (count($listings) < 5) {
            self::markTestSkipped('Need at least 5 listings in test database');
        }

        for ($index = 0; $index < 4; ++$index) {
            $client->request(
                'POST',
                '/api/comparisons/toggle',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['listingId' => $listings[$index]['id']], JSON_THROW_ON_ERROR),
            );
            self::assertResponseIsSuccessful();
        }

        $client->request(
            'POST',
            '/api/comparisons/toggle',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['listingId' => $listings[4]['id']], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(422);
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
