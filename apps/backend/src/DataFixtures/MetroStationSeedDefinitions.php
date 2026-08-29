<?php

declare(strict_types=1);

namespace App\DataFixtures;

final class MetroStationSeedDefinitions
{
    public const LINE_BLUE = '#0072BC';
    public const LINE_RED = '#D62027';
    public const LINE_GREEN = '#009A49';

    /**
     * Full Minsk Metro directory: 3 lines, 36 stations.
     *
     * @return list<array{0: string, 1: string, 2: string}>
     */
    public static function minskStations(): array
    {
        return [
            ['Уручье', 'uruchye', self::LINE_BLUE],
            ['Борисовский тракт', 'borisovskiy-trakt', self::LINE_BLUE],
            ['Восток', 'vostok', self::LINE_BLUE],
            ['Московская', 'moskovskaya', self::LINE_BLUE],
            ['Парк Челюскинцев', 'park-cheluskintsev', self::LINE_BLUE],
            ['Академия наук', 'akademiya-nauk', self::LINE_BLUE],
            ['Площадь Якуба Коласа', 'ploshchad-yakuba-kolasa', self::LINE_BLUE],
            ['Площадь Победы', 'ploshchad-pobedy', self::LINE_BLUE],
            ['Октябрьская', 'oktyabrskaya', self::LINE_BLUE],
            ['Площадь Ленина', 'ploshchad-lenina', self::LINE_BLUE],
            ['Институт культуры', 'institut-kultury', self::LINE_BLUE],
            ['Грушевка', 'grushevka', self::LINE_BLUE],
            ['Михалово', 'mihalovo', self::LINE_BLUE],
            ['Петровщина', 'petrovshchina', self::LINE_BLUE],
            ['Малиновка', 'malinovka', self::LINE_BLUE],

            ['Каменная Горка', 'kamennaya-gorka', self::LINE_RED],
            ['Кунцевщина', 'kuntsevshchina', self::LINE_RED],
            ['Спортивная', 'sportivnaya', self::LINE_RED],
            ['Пушкинская', 'pushkinskaya', self::LINE_RED],
            ['Молодёжная', 'molodyozhnaya', self::LINE_RED],
            ['Фрунзенская', 'frunzenskaya', self::LINE_RED],
            ['Немига', 'nemiga', self::LINE_RED],
            ['Купаловская', 'kupalovskaya', self::LINE_RED],
            ['Первомайская', 'pervomayskaya', self::LINE_RED],
            ['Пролетарская', 'proletarskaya', self::LINE_RED],
            ['Тракторный завод', 'traktornyy-zavod', self::LINE_RED],
            ['Партизанская', 'partizanskaya', self::LINE_RED],
            ['Автозаводская', 'avtozavodskaya', self::LINE_RED],
            ['Могилёвская', 'mogilevskaya', self::LINE_RED],

            ['Юбилейная площадь', 'yubileynaya-ploshchad', self::LINE_GREEN],
            ['Площадь Франтишка Богушевича', 'ploshchad-frantishka-bogushevicha', self::LINE_GREEN],
            ['Вокзальная', 'vokzalnaya', self::LINE_GREEN],
            ['Ковальская Слобода', 'kovalskaya-sloboda', self::LINE_GREEN],
            ['Аэродромная', 'aerodromnaya', self::LINE_GREEN],
            ['Неморшанский сад', 'nemorshanskiy-sad', self::LINE_GREEN],
            ['Слуцкий гостинец', 'slutskiy-gostinets', self::LINE_GREEN],
        ];
    }
}
