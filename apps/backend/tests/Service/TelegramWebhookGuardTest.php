<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\TelegramWebhookGuard;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class TelegramWebhookGuardTest extends TestCase
{
    public function testAllowsDevWithoutSecret(): void
    {
        $guard = new TelegramWebhookGuard('', 'dev');
        self::assertTrue($guard->isAuthorized(Request::create('/api/telegram/webhook', 'POST')));
        self::assertFalse($guard->isSecretConfigured());
    }

    public function testRejectsProdWithoutSecret(): void
    {
        $guard = new TelegramWebhookGuard('', 'prod');
        self::assertFalse($guard->isAuthorized(Request::create('/api/telegram/webhook', 'POST')));
    }

    public function testRequiresMatchingHeaderWhenSecretConfigured(): void
    {
        $guard = new TelegramWebhookGuard('super-secret', 'prod');
        $bad = Request::create('/api/telegram/webhook', 'POST');
        self::assertFalse($guard->isAuthorized($bad));

        $good = Request::create('/api/telegram/webhook', 'POST');
        $good->headers->set(TelegramWebhookGuard::HEADER_NAME, 'super-secret');
        self::assertTrue($guard->isAuthorized($good));
        self::assertTrue($guard->isSecretConfigured());
    }
}
