<?php

declare(strict_types=1);

namespace App\Ai;

final class LocalAiChatFallback
{
    public function reply(string $message, string $locale = 'ru'): string
    {
        $text = mb_strtolower(trim($message));
        $en = $locale === 'en';

        if ($this->matches($text, ['тариф', 'тарифы', 'оплат', 'продвиж', 'premium', 'tariff', 'payment', 'boost'])) {
            return $en
                ? 'Tariffs are in the seller cabinet: Account → Promotion. Pick a plan and pay to boost a listing.'
                : 'Тарифы находятся в кабинете продавца: ЛК → Продвижение. Выберите план и оплатите, чтобы поднять объявление.';
        }

        if ($this->matches($text, ['подать', 'размест', 'создать объяв', 'как размест', 'post a listing', 'how to list', 'create listing'])) {
            return $en
                ? 'To post a listing: open Account → Seller → Create listing, fill the steps and send it for moderation.'
                : 'Чтобы подать объявление: откройте ЛК → Продавец → Подать объявление, заполните шаги и отправьте на модерацию.';
        }

        if ($this->matches($text, ['аренд', 'снять', 'rent'])) {
            return $en
                ? 'For rent: open Rent in the catalog, set city, rooms and price. Use filters for term, deposit and utilities.'
                : 'Для аренды откройте раздел «Аренда», выберите город, комнаты и цену. В фильтрах можно указать срок, залог и коммуналку.';
        }

        if ($this->matches($text, ['минск', 'minsk'])) {
            return $en
                ? 'For Minsk: open Sale or Rent, choose city Minsk, then district or metro. Add rooms and budget in filters.'
                : 'По Минску: откройте «Продажа» или «Аренда», выберите город Минск, затем район или метро. Добавьте комнаты и бюджет в фильтрах.';
        }

        if ($this->matches($text, ['продаж', 'купит', 'купить', 'sale', 'buy'])) {
            return $en
                ? 'For buying: open Sale, set city, rooms, area and price. Verified listings are marked in the card.'
                : 'Для покупки откройте «Продажа», укажите город, комнаты, площадь и цену. Проверенные объявления отмечены в карточке.';
        }

        if ($this->matches($text, ['фильтр', 'поиск', 'найти', 'filter', 'search', 'find'])) {
            return $en
                ? 'Use catalog filters: deal type, city, district, rooms, price and area. You can save a search in your account.'
                : 'Используйте фильтры каталога: тип сделки, город, район, комнаты, цена и площадь. Поиск можно сохранить в ЛК.';
        }

        if ($this->matches($text, ['привет', 'здравств', 'hello', 'hi ', 'hey'])) {
            return $en
                ? 'Hi! I can help with search, filters, posting a listing and tariffs on Renlo.'
                : 'Здравствуйте! Помогу с поиском, фильтрами, подачей объявления и тарифами на Renlo.';
        }

        return $en
            ? 'I can help with catalog search, rent/sale filters, posting a listing and promotion tariffs. Ask a short question.'
            : 'Могу помочь с поиском в каталоге, фильтрами аренды и продажи, подачей объявления и тарифами продвижения. Задайте короткий вопрос.';
    }

    /**
     * @param list<string> $needles
     */
    private function matches(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
