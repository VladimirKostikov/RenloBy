<?php

declare(strict_types=1);

namespace App\Tests\Http;

use App\Http\SeoResponseFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class SeoResponseFactoryTest extends TestCase
{
    public function testXmlResponseHasNoStoreHeaders(): void
    {
        $factory = new SeoResponseFactory();
        $response = $factory->xml('<urlset></urlset>');

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('application/xml; charset=UTF-8', $response->headers->get('Content-Type'));
        self::assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        self::assertSame('no-cache', $response->headers->get('Pragma'));
        self::assertSame('0', $response->headers->get('Expires'));
    }
}
