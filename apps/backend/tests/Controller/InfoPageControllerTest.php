<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class InfoPageControllerTest extends WebTestCase
{
    public function testIndexReturnsInfoPages(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/info-pages');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');

        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertNotEmpty($payload);
        self::assertArrayHasKey('slug', $payload[0]);
    }

    public function testShowReturnsDealSafetyPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/info-pages/deal-safety');

        self::assertResponseIsSuccessful();

        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('deal-safety', $payload['slug']);
        self::assertSame('Руководство по безопасной сделке с недвижимостью', $payload['title']);
        self::assertStringContainsString('## Перед встречей', $payload['body']);
        self::assertNotEmpty($payload['importantNote']);
        self::assertCount(5, $payload['faqItems']);
    }

    public function testShowReturnsNotFoundForUnknownSlug(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/info-pages/unknown-page');

        self::assertResponseStatusCodeSame(404);
    }
}
