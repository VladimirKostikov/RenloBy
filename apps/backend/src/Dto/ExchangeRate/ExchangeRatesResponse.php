<?php

declare(strict_types=1);

namespace App\Dto\ExchangeRate;

readonly class ExchangeRatesResponse
{
    public function __construct(
        public float $usdToByn,
        public float $usdToRub,
        public string $source,
        public ?string $updatedAt,
    ) {
    }
}
