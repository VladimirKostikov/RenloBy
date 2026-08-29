<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class InfoPageAdminControllerTest extends WebTestCase
{
    private const ADMIN_EMAIL = 'admin@renlo.local';
    private const ADMIN_PASSWORD = 'Admin123!';

    public function testAdminInfoPagesRequireAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/info-pages');
        self::assertResponseStatusCodeSame(401);
    }

    public function testAdminCanListAndUpdateInfoPage(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $client->request(
            'GET',
            '/admin/info-pages',
            ['isTest' => '1'],
            [],
            ['HTTP_X_ADMIN_TEST_MODE' => '1'],
        );
        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($list);
        self::assertNotEmpty($list);

        $id = (int) $list[0]['id'];
        $client->request(
            'PATCH',
            '/admin/info-pages/' . $id . '?isTest=1',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_ADMIN_TEST_MODE' => '1',
            ],
            json_encode([
                'title' => 'Обновлённая инфо-страница',
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
        $updated = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Обновлённая инфо-страница', $updated['title']);
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
