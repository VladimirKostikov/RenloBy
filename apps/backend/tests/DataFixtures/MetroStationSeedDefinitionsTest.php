<?php

declare(strict_types=1);

namespace App\Tests\DataFixtures;

use App\DataFixtures\MetroStationSeedDefinitions;
use PHPUnit\Framework\TestCase;

final class MetroStationSeedDefinitionsTest extends TestCase
{
    public function testMinskStationsCoverAllLinesAndAreUnique(): void
    {
        $stations = MetroStationSeedDefinitions::minskStations();

        self::assertCount(36, $stations);

        $slugs = array_column($stations, 1);
        self::assertCount(36, array_unique($slugs));

        $names = array_column($stations, 0);
        self::assertCount(36, array_unique($names));

        $colors = array_column($stations, 2);
        self::assertContains(MetroStationSeedDefinitions::LINE_BLUE, $colors);
        self::assertContains(MetroStationSeedDefinitions::LINE_RED, $colors);
        self::assertContains(MetroStationSeedDefinitions::LINE_GREEN, $colors);

        self::assertContains('Немига', $names);
        self::assertContains('Малиновка', $names);
        self::assertContains('Слуцкий гостинец', $names);
    }
}
