<?php

declare(strict_types=1);

namespace App\DataFixtures;

final class SiteSettingsSeedDefinitions
{
    /**
     * @return array{
     *   aboutText: string,
     *   phoneDisplay: string,
     *   phoneRaw: string,
     *   email: string,
     *   supportHours: string,
     *   ownerName: string,
     *   address: string,
     *   offersText: string,
     *   offersEmail: string,
     *   telegramUrl: string,
     *   whatsappUrl: string,
     *   vkUrl: string
     * }
     */
    public static function defaults(): array
    {
        return [
            'aboutText' => 'Агрегатор покупки, продажи и аренды квартир в Беларуси.',
            'phoneDisplay' => '+375 29 000-00-00',
            'phoneRaw' => '+375290000000',
            'email' => 'support@renlo.by',
            'supportHours' => 'Ежедневно 9:00-18:00',
            'ownerName' => 'Renlo',
            'address' => 'Минск, Беларусь',
            'offersText' => 'По вопросам рекламы и сотрудничества пишите на почту для предложений.',
            'offersEmail' => 'partners@renlo.by',
            'telegramUrl' => 'https://t.me/renlo_bot',
            'whatsappUrl' => 'https://wa.me/375290000000',
            'vkUrl' => 'https://vk.com/renlo',
        ];
    }
}
