<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ExchangeRateControllerTest extends WebTestCase
{
    public function testPublicCanGetExchangeRates(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/exchange-rates');
        self::assertResponseIsSuccessful();

        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('usdToByn', $payload);
        self::assertArrayHasKey('usdToRub', $payload);
        self::assertArrayHasKey('source', $payload);
        self::assertGreaterThan(0, (float) $payload['usdToByn']);
        self::assertContains($payload['source'], ['nbrb', 'fallback']);
    }
}
