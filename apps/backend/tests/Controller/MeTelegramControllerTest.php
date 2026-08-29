<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MeTelegramControllerTest extends WebTestCase
{
    private const PASSWORD = 'SecurePass1';

    public function testTelegramStatusRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/me/telegram');

        self::assertResponseStatusCodeSame(401);
    }

    public function testTelegramStatusReturnsDisconnectedLink(): void
    {
        $client = static::createClient();
        $this->registerUser($client, 'me-telegram@donmap.local');

        $client->request('GET', '/api/me/telegram');
        self::assertResponseIsSuccessful();

        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($payload['connected']);
        self::assertArrayHasKey('connectUrl', $payload);
        self::assertStringContainsString('t.me/', $payload['connectUrl']);
        self::assertStringContainsString('start=s', $payload['connectUrl']);
        self::assertSame('renlo_bot', $payload['botUsername']);
    }

    public function testTelegramDisconnectWhenNotConnectedIsIdempotent(): void
    {
        $client = static::createClient();
        $this->registerUser($client, 'me-telegram-off@donmap.local');

        $client->request('POST', '/api/me/telegram/disconnect');
        self::assertResponseIsSuccessful();

        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($payload['connected']);
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
