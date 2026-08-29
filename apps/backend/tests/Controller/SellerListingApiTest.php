<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SellerListingApiTest extends WebTestCase
{
    private const PASSWORD = 'SecurePass1';

    public function testCreateDraftListingAndPublish(): void
    {
        $client = static::createClient();
        $this->registerUser($client, 'seller-create@donmap.local');
        $this->completeSellerProfile($client);

        $client->request(
            'POST',
            '/api/me/listings',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'dealType' => 'sale',
                'listingType' => 'apartment',
                'price' => 120000,
                'rooms' => 2,
                'area' => 54.5,
                'floor' => 4,
                'totalFloors' => 9,
                'address' => 'ул. Тестовая, 10',
                'latitude' => 53.9,
                'longitude' => 27.56,
                'city' => 'Минск',
                'district' => 'Центр',
                'metro' => 'Немига',
                'fromOwner' => true,
                'priceNegotiable' => true,
                'images' => ['https://example.com/photo.jpg'],
                'status' => 'draft',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('draft', $created['status']);
        self::assertTrue($created['priceNegotiable']);
        self::assertSame('Минск', $created['cityName']);
        self::assertSame('Центр', $created['districtName']);
        self::assertArrayHasKey('id', $created);

        $client->request('POST', '/api/me/listings/' . $created['id'] . '/publish');
        self::assertResponseIsSuccessful();
        $published = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('pending', $published['status']);
    }

    public function testCreateStudioListingWithZeroRooms(): void
    {
        $client = static::createClient();
        $this->registerUser($client, 'seller-studio@donmap.local');
        $this->completeSellerProfile($client);

        $client->request(
            'POST',
            '/api/me/listings',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'dealType' => 'rent',
                'listingType' => 'apartment',
                'price' => 350,
                'rooms' => 0,
                'area' => 28.0,
                'floor' => 5,
                'totalFloors' => 9,
                'address' => 'ул. Студийная, 1',
                'latitude' => 53.91,
                'longitude' => 27.57,
                'city' => 'Минск',
                'district' => 'Центр',
                'rentTerm' => 'long',
                'fromOwner' => true,
                'images' => ['https://example.com/studio.jpg'],
                'status' => 'draft',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(0, $created['rooms']);
    }

    public function testCreateListingWithBrandNewDistrict(): void
    {
        $client = static::createClient();
        $this->registerUser($client, 'seller-new-district@donmap.local');
        $this->completeSellerProfile($client);

        $uniqueDistrict = 'Покровка-' . bin2hex(random_bytes(4));

        $client->request(
            'POST',
            '/api/me/listings',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'dealType' => 'sale',
                'listingType' => 'apartment',
                'price' => 85000,
                'rooms' => 2,
                'area' => 48.0,
                'floor' => 3,
                'totalFloors' => 5,
                'address' => 'ул. Новая, 5',
                'latitude' => 52.78,
                'longitude' => 27.54,
                'city' => 'Солигорск',
                'district' => $uniqueDistrict,
                'fromOwner' => true,
                'images' => ['https://example.com/soligorsk.jpg'],
                'status' => 'draft',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Солигорск', $created['cityName']);
        self::assertSame($uniqueDistrict, $created['districtName']);
        self::assertNotNull($created['districtId'] ?? null);
    }

    public function testCreateRequiresCompleteProfile(): void
    {
        $client = static::createClient();
        $this->registerUser($client, 'seller-incomplete@donmap.local');

        $client->request(
            'POST',
            '/api/me/listings',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'dealType' => 'sale',
                'listingType' => 'apartment',
                'price' => 100000,
                'rooms' => 1,
                'area' => 40,
                'floor' => 1,
                'totalFloors' => 5,
                'address' => 'ул. Тест, 1',
                'latitude' => 53.9,
                'longitude' => 27.56,
                'city' => 'Минск',
                'district' => 'Центр',
                'status' => 'draft',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(422);
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('validation.profile_incomplete', $payload['error'] ?? null);
        self::assertArrayHasKey('lastName', $payload['fields'] ?? []);
        self::assertArrayHasKey('social', $payload['fields'] ?? []);
    }

    public function testCreateWithPublishedStatusGoesToModeration(): void
    {
        $client = static::createClient();
        $this->registerUser($client, 'seller-pending@donmap.local');
        $this->completeSellerProfile($client);

        $client->request(
            'POST',
            '/api/me/listings',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'dealType' => 'sale',
                'listingType' => 'apartment',
                'price' => 99000,
                'rooms' => 1,
                'area' => 40,
                'floor' => 2,
                'totalFloors' => 5,
                'address' => 'ул. Модерация, 1',
                'latitude' => 53.9,
                'longitude' => 27.56,
                'city' => 'Минск',
                'district' => 'Центр',
                'images' => ['https://example.com/moderation.jpg'],
                'status' => 'published',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('pending', $created['status']);
    }

    public function testCreateListingValidationFails(): void
    {
        $client = static::createClient();
        $this->registerUser($client, 'seller-invalid@donmap.local');
        $this->completeSellerProfile($client);

        $client->request(
            'POST',
            '/api/me/listings',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'dealType' => 'sale',
                'listingType' => 'apartment',
                'price' => 0,
                'rooms' => 2,
                'area' => 50,
                'floor' => 1,
                'totalFloors' => 5,
                'address' => '',
                'latitude' => 53.9,
                'longitude' => 27.56,
                'city' => '',
                'district' => '',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testAnalyticsRequiresAuthAndReturnsPayload(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/me/analytics');
        self::assertResponseStatusCodeSame(401);

        $this->registerUser($client, 'seller-analytics@donmap.local');
        $client->request('GET', '/api/me/analytics');
        self::assertResponseIsSuccessful();

        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('listingsCount', $payload);
        self::assertArrayHasKey('totalViews', $payload);
        self::assertArrayHasKey('byDealType', $payload);
        self::assertArrayHasKey('sale', $payload['byDealType']);
        self::assertArrayHasKey('rent', $payload['byDealType']);
        self::assertArrayNotHasKey('commercial', $payload['byDealType']);
        self::assertArrayHasKey('topListings', $payload);
    }

    public function testListingAnalyticsOptionsAndDetail(): void
    {
        $client = static::createClient();
        $this->registerUser($client, 'seller-listing-analytics@donmap.local');
        $this->completeSellerProfile($client);

        $client->request(
            'POST',
            '/api/me/listings',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'dealType' => 'sale',
                'listingType' => 'apartment',
                'price' => 150000,
                'rooms' => 2,
                'area' => 56,
                'floor' => 5,
                'totalFloors' => 9,
                'address' => 'ул. Аналитики, 5',
                'latitude' => 53.9,
                'longitude' => 27.56,
                'city' => 'Минск',
                'district' => 'Центр',
                'images' => ['https://example.com/a.jpg'],
                'status' => 'draft',
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);

        $client->request('GET', '/api/me/analytics/listings');
        self::assertResponseIsSuccessful();
        $options = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($options);
        self::assertArrayHasKey('items', $options);
        self::assertArrayHasKey('total', $options);
        self::assertArrayHasKey('page', $options);
        self::assertArrayHasKey('limit', $options);
        self::assertNotEmpty($options['items']);
        self::assertSame($created['id'], $options['items'][0]['id']);

        $client->request('GET', '/api/me/analytics/listings?q=' . rawurlencode('Аналитики') . '&page=1&limit=20');
        self::assertResponseIsSuccessful();
        $filtered = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(1, $filtered['total']);
        self::assertSame($created['id'], $filtered['items'][0]['id']);

        $client->request('GET', '/api/me/analytics/listings?q=несуществующий-адрес-xyz');
        self::assertResponseIsSuccessful();
        $empty = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(0, $empty['total']);
        self::assertSame([], $empty['items']);

        $client->request('GET', '/api/me/analytics/listings/' . $created['id'] . '?range=week');
        self::assertResponseIsSuccessful();
        $detail = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($created['id'], $detail['listing']['id']);
        self::assertArrayHasKey('viewsSeries', $detail);
        self::assertArrayHasKey('funnel', $detail);
        self::assertArrayHasKey('promotion', $detail);
        self::assertArrayHasKey('engagement', $detail);
        self::assertArrayNotHasKey('tips', $detail);
    }

    public function testDeleteDraftListing(): void
    {
        $client = static::createClient();
        $this->registerUser($client, 'seller-delete-draft@donmap.local');
        $this->completeSellerProfile($client);

        $client->request(
            'POST',
            '/api/me/listings',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'dealType' => 'sale',
                'listingType' => 'apartment',
                'price' => 90000,
                'rooms' => 1,
                'area' => 40,
                'floor' => 2,
                'totalFloors' => 5,
                'address' => 'ул. Черновик, 1',
                'latitude' => 53.9,
                'longitude' => 27.56,
                'city' => 'Минск',
                'district' => 'Центр',
                'images' => ['https://example.com/draft.jpg'],
                'status' => 'draft',
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);

        $client->request('DELETE', '/api/me/listings/' . $created['id']);
        self::assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/me/listings?status=draft');
        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        $ids = array_map(static fn (array $item): int => (int) $item['id'], $list['items'] ?? []);
        self::assertNotContains($created['id'], $ids);
    }

    public function testSellerCanUploadListingMedia(): void
    {
        $client = static::createClient();
        $this->registerUser($client, 'seller-media@donmap.local');

        $tmp = tempnam(sys_get_temp_dir(), 'renlo-listing-');
        self::assertNotFalse($tmp);
        $path = $tmp . '.jpg';
        rename($tmp, $path);
        file_put_contents($path, base64_decode(
            '/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBxISEhUSEhIVFhUVFRUVFRUVFRUWFxUVFRUYHSggGBolGxUVITEhJSkrLi4uFx8zODMtNygtLisBCgoKDg0OGxAQGy0lHyUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLf/AABEIAAEAAQMBIgACEQEDEQH/xAAbAAACAwEBAQAAAAAAAAAAAAADBAECBQYAB//EADYQAAIBAwMCBAMFBQEAAAAAAAECAwAEEQUSITFBEyJRYXGBBjKRoRQjQrHB0fAVYnLh8SQz/8QAGQEAAwEBAQAAAAAAAAAAAAAAAAECAwQF/8QAIhEAAgICAgIDAQEAAAAAAAAAAAECERIhAzFBUQQiYZHwMv/aAAwDAQACEQMRAD8A9o0rSlKUAFFFFABRRRQAUUUUAFFFFAH/2Q==',
            true,
        ));

        $file = new \Symfony\Component\HttpFoundation\File\UploadedFile($path, 'photo.jpg', 'image/jpeg', null, true);
        $client->request('POST', '/api/me/media/upload', [], ['file' => $file]);

        self::assertResponseStatusCodeSame(201);
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('image', $payload['type']);
        self::assertStringStartsWith('/uploads/listings/', $payload['url']);

        $publicPath = static::getContainer()->getParameter('kernel.project_dir') . '/public' . $payload['url'];
        if (is_file($publicPath)) {
            unlink($publicPath);
        }
        @unlink($path);
    }

    public function testUploadListingMediaRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/me/media/upload');
        self::assertResponseStatusCodeSame(401);
    }

    public function testSellerCanUpdateListingSeoMeta(): void
    {
        $client = static::createClient();
        $this->registerUser($client, 'seller-seo@donmap.local');
        $this->completeSellerProfile($client);

        $client->request(
            'POST',
            '/api/me/listings',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'dealType' => 'sale',
                'listingType' => 'apartment',
                'price' => 99000,
                'rooms' => 2,
                'area' => 50,
                'address' => 'ул. SEO, 1',
                'latitude' => 53.9,
                'longitude' => 27.56,
                'city' => 'Минск',
                'district' => 'Центр',
                'images' => ['https://example.com/seo.jpg'],
                'status' => 'draft',
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertNull($created['metaTitle']);
        self::assertNull($created['metaDescription']);
        self::assertNull($created['metaKeywords']);

        $client->request(
            'PATCH',
            '/api/me/listings/' . $created['id'],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'metaTitle' => '2-комнатная квартира в центре Минска',
                'metaDescription' => 'Продажа квартиры 50 м2 в центре Минска без посредников',
                'metaKeywords' => 'квартира, минск, продажа',
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
        $updated = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('2-комнатная квартира в центре Минска', $updated['metaTitle']);
        self::assertSame('Продажа квартиры 50 м2 в центре Минска без посредников', $updated['metaDescription']);
        self::assertSame('квартира, минск, продажа', $updated['metaKeywords']);

        $client->request(
            'PATCH',
            '/api/me/listings/' . $created['id'],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'metaTitle' => '',
                'metaDescription' => null,
                'metaKeywords' => '   ',
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
        $cleared = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertNull($cleared['metaTitle']);
        self::assertNull($cleared['metaDescription']);
        self::assertNull($cleared['metaKeywords']);
    }

    private function registerUser($client, string $email): void
    {
        $uniqueEmail = str_replace('@', '+' . uniqid('', true) . '@', $email);

        $client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $uniqueEmail,
                'password' => self::PASSWORD,
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(201);
    }

    private function completeSellerProfile($client): void
    {
        $client->request(
            'PATCH',
            '/api/auth/me',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'lastName' => 'Иванов',
                'firstName' => 'Иван',
                'patronymic' => 'Иванович',
                'telegram' => '@seller',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
    }
}
