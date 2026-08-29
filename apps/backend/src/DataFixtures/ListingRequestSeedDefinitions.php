<?php

declare(strict_types=1);

namespace App\DataFixtures;

final class ListingRequestSeedDefinitions
{
    /**
     * @return list<array{
     *     phone: string,
     *     message: string,
     *     name: string|null,
     *     status: string,
     *     requesterEmail: string|null
     * }>
     */
    public static function all(): array
    {
        return [
            [
                'phone' => '+375291110001',
                'message' => 'Здравствуйте, хочу посмотреть квартиру на этой неделе.',
                'name' => 'Алексей Покупатель',
                'status' => 'new',
                'requesterEmail' => 'buyer@renlo.local',
            ],
            [
                'phone' => '+375291110002',
                'message' => 'Интересует объект, прошу перезвонить после 18:00.',
                'name' => 'Мария Иванова',
                'status' => 'contacted',
                'requesterEmail' => 'user@renlo.local',
            ],
            [
                'phone' => '+375291110003',
                'message' => 'Готовы обсудить цену и сроки сделки.',
                'name' => null,
                'status' => 'new',
                'requesterEmail' => null,
            ],
            [
                'phone' => '+375291110004',
                'message' => 'Нужна ипотечная консультация по этому объекту.',
                'name' => 'Игорь Смирнов',
                'status' => 'closed',
                'requesterEmail' => 'buyer@renlo.local',
            ],
            [
                'phone' => '+375291110005',
                'message' => 'Можно ли посмотреть объект в выходные?',
                'name' => 'Ольга Козлова',
                'status' => 'new',
                'requesterEmail' => 'user@renlo.local',
            ],
            [
                'phone' => '+375291110006',
                'message' => 'Заявка от агента, клиент заинтересован в покупке.',
                'name' => 'Агент Демо',
                'status' => 'contacted',
                'requesterEmail' => 'agent@renlo.local',
            ],
        ];
    }
}
