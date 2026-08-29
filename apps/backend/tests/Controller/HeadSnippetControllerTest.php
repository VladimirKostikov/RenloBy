<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HeadSnippetControllerTest extends WebTestCase
{
    private const ADMIN_EMAIL = 'admin@renlo.local';
    private const ADMIN_PASSWORD = 'Admin123!';

    public function testPublicListsOnlyEnabledSnippets(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $client->request(
            'POST',
            '/admin/head-snippets?isTest=0',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_ADMIN_TEST_MODE' => '0',
            ],
            json_encode([
                'name' => 'Verification',
                'code' => '<meta name="test-verify" content="ok">',
                'isEnabled' => true,
                'sortOrder' => 1,
                'isTest' => false,
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(201);

        $client->request(
            'POST',
            '/admin/head-snippets?isTest=0',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_ADMIN_TEST_MODE' => '0',
            ],
            json_encode([
                'name' => 'Disabled',
                'code' => '<meta name="hidden" content="no">',
                'isEnabled' => false,
                'sortOrder' => 2,
                'isTest' => false,
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/head-snippets');
        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($list);
        $codes = array_column($list, 'code');
        self::assertContains('<meta name="test-verify" content="ok">', $codes);
        self::assertNotContains('<meta name="hidden" content="no">', $codes);
    }

    public function testAdminHeadSnippetsRequireAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/head-snippets');
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
