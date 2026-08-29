<?php

declare(strict_types=1);

namespace App\DataFixtures;

final class SeoMetaSeedDefinitions
{
    /**
     * @return list<array{pageKey: string, locale: string, title: string, description: string, h1: ?string, keywords: ?string}>
     */
    public static function entries(): array
    {
        return [
            [
                'pageKey' => 'home',
                'locale' => 'ru',
                'title' => 'Renlo - недвижимость в Беларуси',
                'description' => 'Покупка, продажа и аренда квартир в Беларуси. Каталог объявлений, карта и фильтры по городам и районам.',
                'h1' => 'Недвижимость в Беларуси',
                'keywords' => 'недвижимость Беларусь, квартиры Минск, аренда квартир, купить квартиру, Renlo',
            ],
            [
                'pageKey' => 'home',
                'locale' => 'en',
                'title' => 'Renlo - real estate in Belarus',
                'description' => 'Buy, sell and rent apartments in Belarus. Listings catalog, map and filters by city and district.',
                'h1' => 'Real estate in Belarus',
                'keywords' => 'real estate Belarus, apartments Minsk, rent apartment, buy apartment, Renlo',
            ],
            [
                'pageKey' => 'rentCatalog',
                'locale' => 'ru',
                'title' => 'Аренда квартир в Беларуси - Renlo',
                'description' => 'Объявления об аренде квартир, домов и комнат в Беларуси. Фильтры по цене, району и метро.',
                'h1' => 'Аренда недвижимости',
                'keywords' => 'аренда квартир, снять квартиру Беларусь, аренда жилья Минск',
            ],
            [
                'pageKey' => 'rentCatalog',
                'locale' => 'en',
                'title' => 'Apartments for rent in Belarus - Renlo',
                'description' => 'Rent listings for apartments, houses and rooms in Belarus. Filters by price, district and metro.',
                'h1' => 'Rent listings',
                'keywords' => 'apartments for rent, rent apartment Belarus, housing rent Minsk',
            ],
            [
                'pageKey' => 'saleCatalog',
                'locale' => 'ru',
                'title' => 'Продажа квартир в Беларуси - Renlo',
                'description' => 'Объявления о продаже квартир, домов и комнат в Беларуси. Актуальные цены и карта объектов.',
                'h1' => 'Продажа недвижимости',
                'keywords' => 'продажа квартир, купить квартиру Беларусь, недвижимость Минск',
            ],
            [
                'pageKey' => 'saleCatalog',
                'locale' => 'en',
                'title' => 'Apartments for sale in Belarus - Renlo',
                'description' => 'Sale listings for apartments, houses and rooms in Belarus. Current prices and map view.',
                'h1' => 'Sale listings',
                'keywords' => 'apartments for sale, buy apartment Belarus, real estate Minsk',
            ],
            [
                'pageKey' => 'commercialCatalog',
                'locale' => 'ru',
                'title' => 'Коммерческая недвижимость в Беларуси - Renlo',
                'description' => 'Объявления о продаже и аренде коммерческой недвижимости в Беларуси. Офисы, склады и торговые помещения.',
                'h1' => 'Коммерческая недвижимость',
                'keywords' => 'коммерческая недвижимость, офис в аренду, склад Беларусь',
            ],
            [
                'pageKey' => 'commercialCatalog',
                'locale' => 'en',
                'title' => 'Commercial real estate in Belarus - Renlo',
                'description' => 'Commercial property listings in Belarus. Offices, warehouses and retail spaces.',
                'h1' => 'Commercial listings',
                'keywords' => 'commercial real estate, office for rent, warehouse Belarus',
            ],
            [
                'pageKey' => 'searchMap',
                'locale' => 'ru',
                'title' => 'Карта объявлений - Renlo',
                'description' => 'Поиск недвижимости на карте Беларуси. Фильтры по типу сделки, цене и району.',
                'h1' => 'Карта объявлений',
                'keywords' => 'карта недвижимости, объявления на карте, поиск квартир Беларусь',
            ],
            [
                'pageKey' => 'searchMap',
                'locale' => 'en',
                'title' => 'Listings map - Renlo',
                'description' => 'Search real estate on the map of Belarus. Filters by deal type, price and district.',
                'h1' => 'Listings map',
                'keywords' => 'real estate map, listings map, apartment search Belarus',
            ],
        ];
    }
}
