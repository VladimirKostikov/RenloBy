<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SeoMetaControllerTest extends WebTestCase
{
    public function testPublicIndexReturnsSeoMeta(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/seo-meta?locale=ru');

        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertNotEmpty($payload);
        self::assertArrayHasKey('pageKey', $payload[0]);
        self::assertArrayHasKey('title', $payload[0]);
        self::assertArrayHasKey('description', $payload[0]);
    }
}
