<?php

declare(strict_types=1);

namespace App\Telegram;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class HttpTelegramBotClient implements TelegramBotClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $botToken,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->botToken !== '' && $this->botToken !== 'change-me';
    }

    public function sendMessage(string $chatId, string $text): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        try {
            $this->httpClient->request('POST', $this->apiUrl('sendMessage'), [
                'json' => [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'disable_web_page_preview' => true,
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->warning('Telegram sendMessage failed', [
                'chatId' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getUpdates(int $offset = 0, int $limit = 100, int $timeout = 0): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        try {
            $response = $this->httpClient->request('GET', $this->apiUrl('getUpdates'), [
                'query' => [
                    'offset' => max(0, $offset),
                    'limit' => max(1, min(100, $limit)),
                    'timeout' => max(0, min(50, $timeout)),
                    'allowed_updates' => json_encode(['message'], JSON_THROW_ON_ERROR),
                ],
            ]);
            $payload = $response->toArray(false);
            if (!($payload['ok'] ?? false) || !is_array($payload['result'] ?? null)) {
                return [];
            }

            /** @var list<array<string, mixed>> $updates */
            $updates = [];
            foreach ($payload['result'] as $item) {
                if (is_array($item)) {
                    $updates[] = $item;
                }
            }

            return $updates;
        } catch (\Throwable $e) {
            $this->logger->warning('Telegram getUpdates failed', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function getWebhookInfo(): array
    {
        if (!$this->isConfigured()) {
            return [
                'url' => '',
                'pendingUpdateCount' => 0,
                'lastErrorMessage' => null,
            ];
        }

        try {
            $response = $this->httpClient->request('GET', $this->apiUrl('getWebhookInfo'));
            $payload = $response->toArray(false);
            $result = is_array($payload['result'] ?? null) ? $payload['result'] : [];

            return [
                'url' => isset($result['url']) && is_string($result['url']) ? $result['url'] : '',
                'pendingUpdateCount' => isset($result['pending_update_count']) ? (int) $result['pending_update_count'] : 0,
                'lastErrorMessage' => isset($result['last_error_message']) && is_string($result['last_error_message'])
                    ? $result['last_error_message']
                    : null,
            ];
        } catch (\Throwable $e) {
            $this->logger->warning('Telegram getWebhookInfo failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'url' => '',
                'pendingUpdateCount' => 0,
                'lastErrorMessage' => $e->getMessage(),
            ];
        }
    }

    private function apiUrl(string $method): string
    {
        return 'https://api.telegram.org/bot' . $this->botToken . '/' . $method;
    }
}
