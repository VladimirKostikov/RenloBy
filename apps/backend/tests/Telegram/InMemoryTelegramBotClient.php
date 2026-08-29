<?php

declare(strict_types=1);

namespace App\Tests\Telegram;

use App\Telegram\TelegramBotClientInterface;

final class InMemoryTelegramBotClient implements TelegramBotClientInterface
{
    /** @var list<array{chatId: string, text: string}> */
    public array $sent = [];

    /** @var list<array<string, mixed>> */
    public array $updates = [];

    /** @var array{url: string, pendingUpdateCount: int, lastErrorMessage: string|null} */
    public array $webhookInfo = [
        'url' => '',
        'pendingUpdateCount' => 0,
        'lastErrorMessage' => null,
    ];

    public function __construct(
        private readonly bool $configured = true,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    public function sendMessage(string $chatId, string $text): void
    {
        if (!$this->configured) {
            return;
        }

        $this->sent[] = ['chatId' => $chatId, 'text' => $text];
    }

    public function getUpdates(int $offset = 0, int $limit = 100, int $timeout = 0): array
    {
        if (!$this->configured) {
            return [];
        }

        $result = [];
        foreach ($this->updates as $update) {
            $updateId = (int) ($update['update_id'] ?? 0);
            if ($updateId < $offset) {
                continue;
            }
            $result[] = $update;
            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }

    public function getWebhookInfo(): array
    {
        return $this->webhookInfo;
    }
}
