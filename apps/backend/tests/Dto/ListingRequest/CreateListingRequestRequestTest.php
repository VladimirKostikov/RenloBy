<?php

declare(strict_types=1);

namespace App\Tests\Dto\ListingRequest;

use App\Dto\ListingRequest\CreateListingRequestRequest;
use PHPUnit\Framework\TestCase;

final class CreateListingRequestRequestTest extends TestCase
{
    public function testStoresTrimmedPayload(): void
    {
        $dto = new CreateListingRequestRequest(
            phone: '+375291112233',
            message: 'Интересует квартира, прошу перезвонить.',
            name: 'Анна',
        );

        self::assertSame('+375291112233', $dto->phone);
        self::assertSame('Интересует квартира, прошу перезвонить.', $dto->message);
        self::assertSame('Анна', $dto->name);
    }

    public function testAllowsNullName(): void
    {
        $message = str_repeat('а', CreateListingRequestRequest::MESSAGE_MIN_LENGTH);
        $dto = new CreateListingRequestRequest(
            phone: '+375291112233',
            message: $message,
        );

        self::assertNull($dto->name);
        self::assertSame($message, $dto->message);
    }
}
