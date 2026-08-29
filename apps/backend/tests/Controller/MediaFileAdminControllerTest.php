<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MediaFileAdminControllerTest extends WebTestCase
{
    private const ADMIN_EMAIL = 'admin@renlo.local';
    private const ADMIN_PASSWORD = 'Admin123!';

    public function testMediaFilesRequireAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/media-files');
        self::assertResponseStatusCodeSame(401);
    }

    public function testAdminCanListMediaFiles(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $client->request(
            'GET',
            '/admin/media-files',
            ['isTest' => '0'],
            [],
            ['HTTP_X_ADMIN_TEST_MODE' => '0'],
        );
        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
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
