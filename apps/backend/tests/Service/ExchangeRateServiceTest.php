<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\ExchangeRateService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\Cache\CacheInterface;

final class ExchangeRateServiceTest extends TestCase
{
    public function testFetchesUsdAndRubRatesFromNbrb(): void
    {
        $client = new MockHttpClient(static function (string $method, string $url): MockResponse {
            self::assertSame('GET', $method);
            if (str_contains($url, 'USD')) {
                return new MockResponse(json_encode([
                    'Cur_OfficialRate' => 3.2724,
                    'Cur_Scale' => 1,
                    'Date' => '2026-07-16T00:00:00',
                ], JSON_THROW_ON_ERROR));
            }

            return new MockResponse(json_encode([
                'Cur_OfficialRate' => 3.4512,
                'Cur_Scale' => 100,
                'Date' => '2026-07-16T00:00:00',
            ], JSON_THROW_ON_ERROR));
        });

        $service = new ExchangeRateService(
            $client,
            $this->createCache(),
            new NullLogger(),
            3.27,
            93,
        );

        $rates = $service->getRates();

        self::assertSame(3.2724, $rates->usdToByn);
        self::assertEqualsWithDelta(3.2724 / (3.4512 / 100), $rates->usdToRub, 0.01);
        self::assertSame('nbrb', $rates->source);
        self::assertNotNull($rates->updatedAt);
    }

    public function testFallsBackWhenNbrbUnavailable(): void
    {
        $client = new MockHttpClient([
            new MockResponse('', ['http_code' => 500]),
            new MockResponse('', ['http_code' => 500]),
        ]);

        $service = new ExchangeRateService(
            $client,
            $this->createCache(),
            new NullLogger(),
            3.27,
            93,
        );

        $rates = $service->getRates();

        self::assertSame(3.27, $rates->usdToByn);
        self::assertSame(93.0, $rates->usdToRub);
        self::assertSame('fallback', $rates->source);
        self::assertNull($rates->updatedAt);
    }

    private function createCache(): CacheInterface
    {
        return new ArrayAdapter();
    }
}
