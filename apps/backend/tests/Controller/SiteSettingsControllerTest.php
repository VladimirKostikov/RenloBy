<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SiteSettingsControllerTest extends WebTestCase
{
    private const ADMIN_EMAIL = 'admin@renlo.local';
    private const ADMIN_PASSWORD = 'Admin123!';

    public function testPublicCanGetSiteSettings(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/site-settings');
        self::assertResponseIsSuccessful();

        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('aboutText', $payload);
        self::assertArrayHasKey('phoneDisplay', $payload);
        self::assertArrayHasKey('email', $payload);
        self::assertArrayHasKey('telegramUrl', $payload);
        self::assertArrayHasKey('whatsappUrl', $payload);
        self::assertArrayHasKey('vkUrl', $payload);
        self::assertNotSame('', $payload['phoneDisplay']);
    }

    public function testAdminSiteSettingsRequireAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/site-settings');
        self::assertResponseStatusCodeSame(401);
    }

    public function testAdminCanUpdateSiteSettings(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $client->request(
            'GET',
            '/admin/site-settings',
            ['isTest' => '1'],
            [],
            ['HTTP_X_ADMIN_TEST_MODE' => '1'],
        );
        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertNotEmpty($list);
        $id = (int) $list[0]['id'];

        $client->request(
            'PATCH',
            '/admin/site-settings/' . $id . '?isTest=1',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_ADMIN_TEST_MODE' => '1',
            ],
            json_encode([
                'aboutText' => 'Обновлённый текст о сайте',
                'phoneDisplay' => '+375 33 111-22-33',
                'phoneRaw' => '+375331112233',
                'email' => 'hello@renlo.by',
                'telegramUrl' => 'https://t.me/renlo_support',
                'whatsappUrl' => 'https://wa.me/375331112233',
                'vkUrl' => 'https://vk.com/renlo_by',
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
        $updated = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Обновлённый текст о сайте', $updated['aboutText']);
        self::assertSame('+375 33 111-22-33', $updated['phoneDisplay']);
        self::assertSame('hello@renlo.by', $updated['email']);
        self::assertSame('https://t.me/renlo_support', $updated['telegramUrl']);
        self::assertSame('https://wa.me/375331112233', $updated['whatsappUrl']);
        self::assertSame('https://vk.com/renlo_by', $updated['vkUrl']);
    }

    private function loginAsAdmin($client): void
    {
        $client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => self::ADMIN_EMAIL,
                'password' => self::ADMIN_PASSWORD,
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
    }
}
