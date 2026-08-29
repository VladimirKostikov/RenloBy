<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ArticleAdminControllerTest extends WebTestCase
{
    private const ADMIN_EMAIL = 'admin@renlo.local';
    private const ADMIN_PASSWORD = 'Admin123!';

    public function testAdminArticlesRequireAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/articles');
        self::assertResponseStatusCodeSame(401);
    }

    public function testAdminCanListArticlesInTestMode(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $client->request(
            'GET',
            '/admin/articles',
            ['isTest' => '1'],
            [],
            ['HTTP_X_ADMIN_TEST_MODE' => '1'],
        );
        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($list);
        self::assertNotEmpty($list);
        self::assertArrayHasKey('slug', $list[0]);
        self::assertTrue($list[0]['isTest']);
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
