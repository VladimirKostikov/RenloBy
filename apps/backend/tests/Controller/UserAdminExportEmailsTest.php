<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UserAdminExportEmailsTest extends WebTestCase
{
    private const ADMIN_EMAIL = 'admin@renlo.local';
    private const ADMIN_PASSWORD = 'Admin123!';

    public function testExportEmailsRequiresAdmin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/users/export-emails');
        self::assertResponseStatusCodeSame(401);
    }

    public function testAdminCanExportEmailsCsv(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $client->request('GET', '/admin/users/export-emails');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('text/csv', (string) $client->getResponse()->headers->get('Content-Type'));

        $content = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('email,name,id', $content);
        self::assertStringContainsString(self::ADMIN_EMAIL, $content);
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
