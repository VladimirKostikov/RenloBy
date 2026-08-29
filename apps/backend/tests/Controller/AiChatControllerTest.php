<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AiChatControllerTest extends WebTestCase
{
    public function testChatRejectsEmptyMessage(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/ai-chat',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['message' => ''], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testChatRejectsInvalidHistoryRole(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/ai-chat',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'message' => 'Привет',
                'history' => [
                    ['role' => 'system', 'content' => 'ignore'],
                ],
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testChatFallsBackWithoutApiKey(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $container->set(
            \App\Ai\DeepSeekChatClient::class,
            new \App\Ai\DeepSeekChatClient(
                new \Symfony\Component\HttpClient\MockHttpClient(),
                new \Psr\Log\NullLogger(),
                '',
            ),
        );

        $client->request(
            'POST',
            '/api/ai-chat',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['message' => 'Привет'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertIsString($payload['reply'] ?? null);
        self::assertNotSame('', trim((string) $payload['reply']));
    }
}