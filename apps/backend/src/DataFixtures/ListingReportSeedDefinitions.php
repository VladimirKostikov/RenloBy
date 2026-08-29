<?php

declare(strict_types=1);

namespace App\DataFixtures;

final class ListingReportSeedDefinitions
{
    /**
     * @return list<array{
     *     reason: string,
     *     comment: string|null,
     *     status: string
     * }>
     */
    public static function all(): array
    {
        return [
            [
                'reason' => 'spam',
                'comment' => 'Похоже на повтор объявления с другого аккаунта.',
                'status' => 'new',
            ],
            [
                'reason' => 'wrong',
                'comment' => 'Адрес и фото не совпадают с описанием.',
                'status' => 'reviewed',
            ],
            [
                'reason' => 'fraud',
                'comment' => 'Просят предоплату до показа объекта.',
                'status' => 'new',
            ],
            [
                'reason' => 'other',
                'comment' => 'Устаревшая цена, объект уже сдан.',
                'status' => 'closed',
            ],
            [
                'reason' => 'spam',
                'comment' => null,
                'status' => 'new',
            ],
            [
                'reason' => 'wrong',
                'comment' => 'Неверная площадь в карточке.',
                'status' => 'reviewed',
            ],
        ];
    }
}
