<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TelegramWebhookControllerTest extends WebTestCase
{
    private const ADMIN_EMAIL = 'admin@renlo.local';
    private const ADMIN_PASSWORD = 'Admin123!';

    public function testWebhookConnectsSubscriberOnStart(): void
    {
        $client = static::createClient();
        $chatId = '100' . (string) random_int(100000, 999999);

        $client->request(
            'POST',
            '/api/telegram/webhook',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'message' => [
                    'chat' => ['id' => (int) $chatId],
                    'from' => ['username' => 'admin_tg', 'first_name' => 'Admin'],
                    'text' => '/start connect',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($body['ok']);

        $this->loginAsAdmin($client);
        $client->request('GET', '/admin/telegram/status');
        self::assertResponseIsSuccessful();
        $status = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('renlo_bot', $status['botUsername']);
        self::assertIsArray($status['subscribers']);

        $found = false;
        foreach ($status['subscribers'] as $subscriber) {
            if (($subscriber['chatId'] ?? '') === $chatId) {
                $found = true;
                self::assertTrue($subscriber['isActive']);
                break;
            }
        }
        self::assertTrue($found);
    }

    public function testTelegramStatusRequiresAdmin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/telegram/status');
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
}
