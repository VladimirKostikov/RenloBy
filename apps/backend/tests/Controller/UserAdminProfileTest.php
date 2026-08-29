<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UserAdminProfileTest extends WebTestCase
{
    private const ADMIN_EMAIL = 'admin@renlo.local';
    private const ADMIN_PASSWORD = 'Admin123!';

    public function testAdminCanUpdateUserProfileFields(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $client->request('GET', '/admin/users');
        self::assertResponseIsSuccessful();
        $users = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($users);
        self::assertNotEmpty($users);
        $userId = (int) $users[0]['id'];

        $client->request(
            'PATCH',
            '/admin/users/' . $userId,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'lastName' => 'Admin',
                'firstName' => 'Updated',
                'patronymic' => 'Name',
                'phone' => '+375299998877',
                'instagram' => '@admin',
                'telegram' => '@admin_tg',
                'whatsapp' => '+375299998877',
                'viber' => '+375299998877',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Admin Updated Name', $payload['name']);
        self::assertSame('Admin', $payload['lastName']);
        self::assertSame('Updated', $payload['firstName']);
        self::assertSame('Name', $payload['patronymic']);
        self::assertSame('+375299998877', $payload['phone']);
        self::assertSame('@admin', $payload['instagram']);
        self::assertSame('@admin_tg', $payload['telegram']);
        self::assertSame('+375299998877', $payload['whatsapp']);
        self::assertSame('+375299998877', $payload['viber']);
    }

    public function testAdminPhotoUploadRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('POST', '/admin/users/1/photo');
        self::assertResponseStatusCodeSame(401);
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
