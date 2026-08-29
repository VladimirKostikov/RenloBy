<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ArticleControllerTest extends WebTestCase
{
    public function testIndexReturnsPublishedArticles(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/articles');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');

        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertNotEmpty($payload);
        self::assertArrayHasKey('slug', $payload[0]);
        self::assertArrayHasKey('excerpt', $payload[0]);
        self::assertArrayHasKey('coverImage', $payload[0]);
        self::assertArrayHasKey('media', $payload[0]);
        self::assertNotEmpty($payload[0]['coverImage']);
        self::assertIsArray($payload[0]['media']);
        self::assertNotEmpty($payload[0]['media']);
        self::assertTrue($payload[0]['isPublished']);
    }

    public function testShowReturnsArticleBySlug(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/articles/kak-vybrat-kvartiru-v-minske');

        self::assertResponseIsSuccessful();

        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('kak-vybrat-kvartiru-v-minske', $payload['slug']);
        self::assertSame('Как выбрать квартиру в Минске', $payload['title']);
        self::assertStringContainsString('## С чего начать', $payload['body']);
        self::assertNotEmpty($payload['metaTitle']);
        self::assertNotEmpty($payload['coverImage']);
        self::assertNotEmpty($payload['media']);
    }

    public function testShowReturnsNotFoundForUnknownSlug(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/articles/unknown-article');

        self::assertResponseStatusCodeSame(404);
    }
}
