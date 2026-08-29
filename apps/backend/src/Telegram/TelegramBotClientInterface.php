<?php

declare(strict_types=1);

namespace App\Telegram;

interface TelegramBotClientInterface
{
    public function sendMessage(string $chatId, string $text): void;

    public function isConfigured(): bool;

    /**
     * @return list<array<string, mixed>>
     */
    public function getUpdates(int $offset = 0, int $limit = 100, int $timeout = 0): array;

    /**
     * @return array{url: string, pendingUpdateCount: int, lastErrorMessage: string|null}
     */
    public function getWebhookInfo(): array;
}
