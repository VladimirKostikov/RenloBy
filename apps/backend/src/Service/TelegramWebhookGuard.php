<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

final class TelegramWebhookGuard
{
    public const HEADER_NAME = 'X-Telegram-Bot-Api-Secret-Token';

    public function __construct(
        private readonly string $webhookSecret,
        private readonly string $appEnv,
    ) {
    }

    public function isAuthorized(Request $request): bool
    {
        $secret = trim($this->webhookSecret);
        if ($secret === '') {
            return $this->appEnv !== 'prod';
        }

        $provided = $request->headers->get(self::HEADER_NAME, '');
        if (!is_string($provided) || $provided === '') {
            return false;
        }

        return hash_equals($secret, $provided);
    }

    public function isSecretConfigured(): bool
    {
        return trim($this->webhookSecret) !== '';
    }
}
