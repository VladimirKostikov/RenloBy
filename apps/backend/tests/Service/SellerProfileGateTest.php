<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Exception\ValidationException;
use App\Http\ApiErrorCode;
use App\Service\SellerProfileGate;
use PHPUnit\Framework\TestCase;

final class SellerProfileGateTest extends TestCase
{
    public function testIncompleteProfileFailsWithMissingFields(): void
    {
        $gate = new SellerProfileGate();
        $user = new User();

        try {
            $gate->assertComplete($user);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(ApiErrorCode::VALIDATION_PROFILE_INCOMPLETE, $exception->getMessage());
            self::assertArrayHasKey('lastName', $exception->fields);
            self::assertArrayHasKey('firstName', $exception->fields);
            self::assertArrayHasKey('patronymic', $exception->fields);
            self::assertArrayHasKey('social', $exception->fields);
        }
    }

    public function testCompleteProfilePasses(): void
    {
        $gate = new SellerProfileGate();
        $user = (new User())
            ->setNameParts('Иванов', 'Иван', 'Иванович')
            ->setTelegram('@ivan');

        self::assertTrue($gate->isComplete($user));
        $gate->assertComplete($user);
        self::assertSame([], $gate->missingFields($user));
    }

    public function testOneSocialIsEnough(): void
    {
        $gate = new SellerProfileGate();
        $user = (new User())
            ->setNameParts('Иванов', 'Иван', 'Иванович')
            ->setViber('+375291112233');

        self::assertTrue($gate->isComplete($user));
        self::assertArrayNotHasKey('social', $gate->missingFields($user));
    }
}
