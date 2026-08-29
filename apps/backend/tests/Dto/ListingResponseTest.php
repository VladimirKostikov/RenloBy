<?php

declare(strict_types=1);

namespace App\Tests\Dto;

use App\Dto\Listing\ListingResponse;
use App\Entity\City;
use App\Entity\District;
use App\Entity\Listing;
use App\Entity\User;
use App\Enum\DealType;
use App\Enum\ListingStatus;
use App\Enum\ListingType;
use PHPUnit\Framework\TestCase;

final class ListingResponseTest extends TestCase
{
    public function testIncludesPublicSellerWithoutEmail(): void
    {
        $user = (new User())
            ->setEmail('seller@example.com')
            ->setName('Иван Продавец')
            ->setPassword('hashed')
            ->setRoles(['ROLE_USER'])
            ->setPhone('+375291112233')
            ->setTelegram('ivan_seller')
            ->setWhatsapp('+375291112233')
            ->setPhoto('https://example.com/avatar.jpg');

        $reflection = new \ReflectionProperty(User::class, 'id');
        $reflection->setValue($user, 15);

        $city = (new City())->setName('Минск')->setSlug('minsk')->setRegionSlug('minsk-city');
        $district = (new District())->setCity($city)->setName('Центральный')->setSlug('central');

        $listing = (new Listing())
            ->setUser($user)
            ->setCity($city)
            ->setDistrict($district)
            ->setDealType(DealType::Sale)
            ->setListingType(ListingType::Apartment)
            ->setStatus(ListingStatus::Published)
            ->setPrice(120000)
            ->setPricePerSqm(2400)
            ->setRooms(2)
            ->setArea(50.0)
            ->setFloor(5)
            ->setTotalFloors(12)
            ->setAddress('ул. Тестовая, 1')
            ->setLatitude(53.9)
            ->setLongitude(27.5)
            ->setImages([])
            ->setPublishedAt(new \DateTimeImmutable('2026-07-01T10:00:00+00:00'));

        $response = ListingResponse::fromEntity($listing);
        $payload = json_decode(json_encode($response, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(15, $response->userId);
        self::assertNotNull($response->seller);
        self::assertSame('Иван Продавец', $response->seller->name);
        self::assertSame('+375291112233', $response->seller->phone);
        self::assertSame('ivan_seller', $response->seller->telegram);
        self::assertArrayHasKey('seller', $payload);
        self::assertArrayNotHasKey('email', $payload['seller']);
        self::assertSame('Иван Продавец', $payload['seller']['name']);
    }
}
