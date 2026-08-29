<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ValidationException;
use App\Http\ApiErrorCode;

final class CurrencyConverter
{
    private const SUPPORTED = ['USD', 'BYN', 'RUB'];

    private float $usdToByn;
    private float $usdToRub;

    public function __construct(string|float $usdToByn, string|float $usdToRub)
    {
        $this->usdToByn = (float) $usdToByn;
        $this->usdToRub = (float) $usdToRub;
        if ($this->usdToByn <= 0) {
            $this->usdToByn = 3.27;
        }
        if ($this->usdToRub <= 0) {
            $this->usdToRub = 93.0;
        }
    }

    /**
     * @return list<string>
     */
    public function supportedCurrencies(): array
    {
        return self::SUPPORTED;
    }

    public function assertSupported(string $currency): string
    {
        $normalized = strtoupper(trim($currency));
        if (!in_array($normalized, self::SUPPORTED, true)) {
            throw new ValidationException(['currency' => ApiErrorCode::VALIDATION_FAILED]);
        }

        return $normalized;
    }

    public function fromUsd(string $amountUsd, string $currency): string
    {
        $code = $this->assertSupported($currency);
        $usd = (float) str_replace(',', '.', $amountUsd);

        return match ($code) {
            'BYN' => $this->formatAmount(round($usd * $this->usdToByn)),
            'RUB' => $this->formatAmount(round($usd * $this->usdToRub / 10) * 10),
            default => $this->formatAmount($usd),
        };
    }

    public function usdToBynAmount(int|float $usd): float
    {
        return round(((float) $usd) * $this->usdToByn, 2);
    }

    public function usdToBynRate(): float
    {
        return $this->usdToByn;
    }

    private function formatAmount(float $amount): string
    {
        if ($amount <= 0) {
            return '0.01';
        }

        return number_format($amount, 2, '.', '');
    }
}
