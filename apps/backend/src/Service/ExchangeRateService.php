<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ExchangeRate\ExchangeRatesResponse;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ExchangeRateService
{
    private const USD_URL = 'https://www.nbrb.by/api/exrates/rates/USD?parammode=2';
    private const RUB_URL = 'https://www.nbrb.by/api/exrates/rates/RUB?parammode=2';
    private const CACHE_KEY = 'exchange_rates.nbrb.v1';
    private const CACHE_TTL_SECONDS = 3600;

    private float $fallbackUsdToByn;
    private float $fallbackUsdToRub;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
        string|float $usdToByn,
        string|float $usdToRub,
    ) {
        $this->fallbackUsdToByn = (float) $usdToByn;
        $this->fallbackUsdToRub = (float) $usdToRub;
        if ($this->fallbackUsdToByn <= 0) {
            $this->fallbackUsdToByn = 3.27;
        }
        if ($this->fallbackUsdToRub <= 0) {
            $this->fallbackUsdToRub = 93.0;
        }
    }

    public function getRates(): ExchangeRatesResponse
    {
        return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item): ExchangeRatesResponse {
            $item->expiresAfter(self::CACHE_TTL_SECONDS);

            try {
                return $this->fetchFromNbrb();
            } catch (\Throwable $exception) {
                $this->logger->warning('Failed to fetch NBRB exchange rates', [
                    'error' => $exception->getMessage(),
                ]);

                return $this->fallbackRates();
            }
        });
    }

    public function getUsdToByn(): float
    {
        return $this->getRates()->usdToByn;
    }

    private function fetchFromNbrb(): ExchangeRatesResponse
    {
        $usd = $this->fetchCurrencyRate(self::USD_URL);
        $rub = $this->fetchCurrencyRate(self::RUB_URL);
        $bynPerRub = $rub['rate'];
        $usdToRub = $bynPerRub > 0
            ? $usd['rate'] / $bynPerRub
            : $this->fallbackUsdToRub;

        return new ExchangeRatesResponse(
            usdToByn: round($usd['rate'], 4),
            usdToRub: round($usdToRub, 4),
            source: 'nbrb',
            updatedAt: $usd['date'] ?? $rub['date'],
        );
    }

    /**
     * @return array{rate: float, date: ?string}
     */
    private function fetchCurrencyRate(string $url): array
    {
        $response = $this->httpClient->request('GET', $url, [
            'timeout' => 5,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('nbrb_http_' . $response->getStatusCode());
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->toArray(false);
        $officialRate = isset($payload['Cur_OfficialRate']) ? (float) $payload['Cur_OfficialRate'] : 0.0;
        $scale = isset($payload['Cur_Scale']) ? (float) $payload['Cur_Scale'] : 1.0;
        if ($officialRate <= 0 || $scale <= 0) {
            throw new \RuntimeException('nbrb_invalid_rate');
        }

        $date = null;
        if (isset($payload['Date']) && is_string($payload['Date']) && $payload['Date'] !== '') {
            $date = (new \DateTimeImmutable($payload['Date']))->format(\DateTimeInterface::ATOM);
        }

        return [
            'rate' => $officialRate / $scale,
            'date' => $date,
        ];
    }

    private function fallbackRates(): ExchangeRatesResponse
    {
        return new ExchangeRatesResponse(
            usdToByn: $this->fallbackUsdToByn,
            usdToRub: $this->fallbackUsdToRub,
            source: 'fallback',
            updatedAt: null,
        );
    }
}
