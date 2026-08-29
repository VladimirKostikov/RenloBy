<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Ai\DeepSeekChatClient;
use App\Ai\LocalAiChatFallback;
use App\Dto\AiChat\AiChatHistoryItem;
use App\Dto\AiChat\AiChatRequest;
use App\Service\AiChatService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class AiChatServiceTest extends TestCase
{
    public function testChatReturnsAssistantReply(): void
    {
        $mock = new MockHttpClient(static function (string $method, string $url, array $options): MockResponse {
            self::assertSame('POST', $method);
            self::assertStringContainsString('api.deepseek.com', $url);
            $body = json_decode((string) ($options['body'] ?? ''), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('deepseek-chat', $body['model']);
            self::assertSame('system', $body['messages'][0]['role']);
            self::assertSame('user', $body['messages'][1]['role']);
            self::assertSame('Ищу двушку в Минске', $body['messages'][1]['content']);

            return new MockResponse(json_encode([
                'choices' => [
                    ['message' => ['role' => 'assistant', 'content' => ' Могу помочь с фильтрами по Минску. ']],
                ],
            ], JSON_THROW_ON_ERROR));
        });

        $service = new AiChatService(
            new DeepSeekChatClient($mock, new NullLogger(), 'test-key'),
            new LocalAiChatFallback(),
            new NullLogger(),
        );
        $response = $service->chat(new AiChatRequest('Ищу двушку в Минске'));

        self::assertSame('Могу помочь с фильтрами по Минску.', $response->reply);
    }

    public function testChatIncludesHistory(): void
    {
        $mock = new MockHttpClient(static function (string $method, string $url, array $options): MockResponse {
            $body = json_decode((string) ($options['body'] ?? ''), true, 512, JSON_THROW_ON_ERROR);
            self::assertCount(4, $body['messages']);
            self::assertSame('assistant', $body['messages'][1]['role']);
            self::assertSame('Какой бюджет?', $body['messages'][1]['content']);
            self::assertSame('user', $body['messages'][2]['role']);
            self::assertSame('До 100 тысяч', $body['messages'][2]['content']);
            self::assertSame('user', $body['messages'][3]['role']);
            self::assertSame('Есть варианты?', $body['messages'][3]['content']);

            return new MockResponse(json_encode([
                'choices' => [
                    ['message' => ['role' => 'assistant', 'content' => 'Да, откройте фильтр цены.']],
                ],
            ], JSON_THROW_ON_ERROR));
        });

        $service = new AiChatService(
            new DeepSeekChatClient($mock, new NullLogger(), 'test-key'),
            new LocalAiChatFallback(),
            new NullLogger(),
        );
        $response = $service->chat(new AiChatRequest('Есть варианты?', [
            new AiChatHistoryItem('assistant', 'Какой бюджет?'),
            new AiChatHistoryItem('user', 'До 100 тысяч'),
        ]));

        self::assertSame('Да, откройте фильтр цены.', $response->reply);
    }

    public function testChatUsesEnglishSystemPrompt(): void
    {
        $mock = new MockHttpClient(static function (string $method, string $url, array $options): MockResponse {
            $body = json_decode((string) ($options['body'] ?? ''), true, 512, JSON_THROW_ON_ERROR);
            self::assertStringContainsString('Reply in English', $body['messages'][0]['content']);
            self::assertSame('Looking for a flat', $body['messages'][1]['content']);

            return new MockResponse(json_encode([
                'choices' => [
                    ['message' => ['role' => 'assistant', 'content' => 'I can help with filters.']],
                ],
            ], JSON_THROW_ON_ERROR));
        });

        $service = new AiChatService(
            new DeepSeekChatClient($mock, new NullLogger(), 'test-key'),
            new LocalAiChatFallback(),
            new NullLogger(),
        );
        $response = $service->chat(new AiChatRequest('Looking for a flat', [], 'en'));

        self::assertSame('I can help with filters.', $response->reply);
    }

    public function testChatFallsBackWithoutApiKey(): void
    {
        $service = new AiChatService(
            new DeepSeekChatClient(new MockHttpClient(), new NullLogger(), ''),
            new LocalAiChatFallback(),
            new NullLogger(),
        );

        $response = $service->chat(new AiChatRequest('Как подать объявление?'));

        self::assertStringContainsString('Подать объявление', $response->reply);
    }

    public function testChatFallsBackWhenProviderReturnsError(): void
    {
        $mock = new MockHttpClient(static fn (): MockResponse => new MockResponse(
            json_encode(['error' => ['message' => 'Insufficient Balance']], JSON_THROW_ON_ERROR),
            ['http_code' => 402],
        ));

        $service = new AiChatService(
            new DeepSeekChatClient($mock, new NullLogger(), 'test-key'),
            new LocalAiChatFallback(),
            new NullLogger(),
        );

        $response = $service->chat(new AiChatRequest('Ищу квартиру в Минске'));

        self::assertStringContainsString('Минск', $response->reply);
    }
}
