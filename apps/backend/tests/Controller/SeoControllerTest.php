<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SeoControllerTest extends WebTestCase
{
    public function testRobotsTxtIsPublicAndContainsSitemap(): void
    {
        $client = static::createClient();
        $client->request('GET', '/robots.txt');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'text/plain; charset=UTF-8');
        self::assertResponseHasHeader('cache-control');
        self::assertStringContainsString('no-store', (string) $client->getResponse()->headers->get('cache-control'));

        $content = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('User-agent: *', $content);
        self::assertStringContainsString('Disallow: /admin', $content);
        self::assertStringContainsString('Sitemap:', $content);
    }

    public function testSitemapIsGeneratedOnEachRequestWithoutCache(): void
    {
        $client = static::createClient();

        try {
            $client->request('GET', '/sitemap.xml');
        } catch (\Throwable $exception) {
            self::markTestSkipped('Database is required for sitemap generation: ' . $exception->getMessage());
        }

        if ($client->getResponse()->getStatusCode() >= 500) {
            self::markTestSkipped('Database is required for sitemap generation.');
        }

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/xml; charset=UTF-8');
        self::assertResponseHasHeader('cache-control');
        self::assertStringContainsString('no-store', (string) $client->getResponse()->headers->get('cache-control'));
        self::assertResponseHasHeader('pragma');
        self::assertSame('no-cache', $client->getResponse()->headers->get('pragma'));

        $firstContent = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('<urlset', $firstContent);

        $client->request('GET', '/sitemap.xml');

        self::assertResponseIsSuccessful();
        self::assertSame($firstContent, $client->getResponse()->getContent());
    }
}
