<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SeoMetaAdminControllerTest extends WebTestCase
{
    private const ADMIN_EMAIL = 'admin@renlo.local';
    private const ADMIN_PASSWORD = 'Admin123!';

    public function testAdminIndexRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/seo-meta');

        self::assertResponseStatusCodeSame(401);
    }

    public function testAdminCanListAndUpdateSeoMeta(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $client->request('GET', '/admin/seo-meta');
        self::assertResponseIsSuccessful();

        $list = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($list);
        self::assertNotEmpty($list);
        self::assertArrayHasKey('isTest', $list[0]);
        self::assertTrue($list[0]['isTest']);

        $id = (int) $list[0]['id'];
        $client->request(
            'PATCH',
            '/admin/seo-meta/' . $id,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Updated SEO title',
                'description' => 'Updated SEO description',
                'h1' => 'Updated H1',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        $updated = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Updated SEO title', $updated['title']);
        self::assertSame('Updated SEO description', $updated['description']);
        self::assertSame('Updated H1', $updated['h1']);
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
