<?php

declare(strict_types=1);

namespace App\DataFixtures;

final class TariffSeedDefinitions
{
    /**
     * @return list<array{
     *     code: string,
     *     priceUsd: string,
     *     priceByn: string,
     *     priceRub: string,
     *     isPopular: bool,
     *     sortOrder: int
     * }>
     */
    public static function all(): array
    {
        return [
            [
                'code' => 'basic',
                'priceUsd' => '9.90',
                'priceByn' => '32.00',
                'priceRub' => '920.00',
                'isPopular' => false,
                'sortOrder' => 10,
            ],
            [
                'code' => 'standard',
                'priceUsd' => '19.90',
                'priceByn' => '65.00',
                'priceRub' => '1850.00',
                'isPopular' => true,
                'sortOrder' => 20,
            ],
            [
                'code' => 'premium',
                'priceUsd' => '34.90',
                'priceByn' => '114.00',
                'priceRub' => '3250.00',
                'isPopular' => false,
                'sortOrder' => 30,
            ],
        ];
    }
}
