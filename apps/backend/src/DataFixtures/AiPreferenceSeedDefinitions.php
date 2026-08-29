<?php

declare(strict_types=1);

namespace App\DataFixtures;

final class AiPreferenceSeedDefinitions
{
    /**
     * @return list<array{
     *   answers: array<string, mixed>,
     *   filters: array<string, mixed>,
     *   summary: string,
     *   highlights: list<string>
     * }>
     */
    public static function entries(): array
    {
        return [
            [
                'answers' => [
                    'dealType' => 'rent',
                    'currency' => 'byn',
                    'budgetMin' => 300,
                    'budgetMax' => 600,
                    'rooms' => 2,
                    'cityId' => null,
                    'priorities' => ['fromOwner', 'noCommission'],
                ],
                'filters' => [
                    'dealType' => 'rent',
                    'minPrice' => 300,
                    'maxPrice' => 600,
                    'rooms' => 2,
                    'fromOwner' => true,
                    'noCommission' => true,
                ],
                'summary' => 'Подборка аренды 2-комнатных квартир без комиссии от собственника в бюджете 300-600 USD.',
                'highlights' => [
                    'Аренда',
                    '2 комнаты',
                    'От собственника',
                    'Без комиссии',
                ],
            ],
        ];
    }
}
