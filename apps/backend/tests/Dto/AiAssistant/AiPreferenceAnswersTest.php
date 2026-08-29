<?php

declare(strict_types=1);

namespace App\Tests\Dto\AiAssistant;

use App\Dto\AiAssistant\AiPreferenceAnswers;
use PHPUnit\Framework\TestCase;

final class AiPreferenceAnswersTest extends TestCase
{
    public function testFromArrayNormalizesAndWhitelists(): void
    {
        $answers = AiPreferenceAnswers::fromArray([
            'dealType' => 'sale',
            'currency' => 'BYN',
            'budgetMin' => 800,
            'budgetMax' => 500,
            'rooms' => 2,
            'cityId' => 1,
            'priorities' => ['fromOwner', 'hack', 'nearMetro', 'fromOwner'],
        ]);

        self::assertSame('sale', $answers->dealType);
        self::assertSame('byn', $answers->currency);
        self::assertSame(500, $answers->budgetMin);
        self::assertSame(800, $answers->budgetMax);
        self::assertSame(2, $answers->rooms);
        self::assertSame(1, $answers->cityId);
        self::assertSame(['fromOwner', 'nearMetro'], $answers->priorities);
    }

    public function testStudioRoomsAreAccepted(): void
    {
        $answers = AiPreferenceAnswers::fromArray(['rooms' => 0]);

        self::assertSame(0, $answers->rooms);
    }

    public function testInvalidDealTypeFallsBackToRent(): void
    {
        $answers = AiPreferenceAnswers::fromArray(['dealType' => 'unknown']);

        self::assertSame('rent', $answers->dealType);
        self::assertSame('byn', $answers->currency);
    }

    public function testCommercialDealTypeFallsBackToRent(): void
    {
        $answers = AiPreferenceAnswers::fromArray(['dealType' => 'commercial']);

        self::assertSame('rent', $answers->dealType);
    }

    public function testInvalidCurrencyFallsBackToByn(): void
    {
        $answers = AiPreferenceAnswers::fromArray(['currency' => 'eur']);

        self::assertSame('byn', $answers->currency);
    }
}
