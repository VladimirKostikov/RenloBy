<?php

declare(strict_types=1);

namespace App\Tests\Dto\Seller;

use App\Dto\Seller\SellerProfileResponse;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class SellerProfileResponseTest extends TestCase
{
    public function testIncludesRegisteredAtFromUser(): void
    {
        $registeredAt = new \DateTimeImmutable('2025-03-12T10:00:00+00:00');
        $user = (new User())
            ->setEmail('seller@renlo.local')
            ->setName('Seller')
            ->setPassword('hash')
            ->setInstagram('@seller.ig')
            ->setTelegram('@seller_tg')
            ->setRegisteredAt($registeredAt);

        $response = SellerProfileResponse::fromEntity($user, 3);

        self::assertSame($registeredAt->format(\DateTimeInterface::ATOM), $response->registeredAt);
        self::assertNull($response->lastSeenAt);
        self::assertSame(3, $response->listingsCount);
        self::assertSame('@seller.ig', $response->instagram);
        self::assertSame('@seller_tg', $response->telegram);
    }
}
