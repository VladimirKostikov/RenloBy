<?php

declare(strict_types=1);

namespace App\Tests\Ai;

use App\Ai\LocalAiChatFallback;
use PHPUnit\Framework\TestCase;

final class LocalAiChatFallbackTest extends TestCase
{
    public function testRepliesAboutTariffs(): void
    {
        $fallback = new LocalAiChatFallback();
        $reply = $fallback->reply('Как работают тарифы?');

        self::assertStringContainsString('Продвижение', $reply);
    }

    public function testEnglishLocale(): void
    {
        $fallback = new LocalAiChatFallback();
        $reply = $fallback->reply('How to post a listing', 'en');

        self::assertStringContainsString('Create listing', $reply);
    }
}
