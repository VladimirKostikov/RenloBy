<?php

declare(strict_types=1);

namespace App\Tests\DataFixtures;

use App\DataFixtures\AdminNationwideListingSeedDefinitions;
use App\Enum\DealType;
use App\Enum\ListingType;
use App\Enum\RentTerm;
use PHPUnit\Framework\TestCase;

final class AdminNationwideListingSeedDefinitionsTest extends TestCase
{
    public function testContainsFiftyListingsAcrossBelarus(): void
    {
        $rows = AdminNationwideListingSeedDefinitions::all();

        self::assertCount(50, $rows);
        self::assertSame('admin@renlo.local', AdminNationwideListingSeedDefinitions::ADMIN_EMAIL);

        $citySlugs = array_unique(array_map(static fn (array $row): string => $row['citySlug'], $rows));
        self::assertContains('minsk', $citySlugs);
        self::assertContains('brest-city', $citySlugs);
        self::assertContains('vitebsk-city', $citySlugs);
        self::assertContains('gomel-city', $citySlugs);
        self::assertContains('grodno-city', $citySlugs);
        self::assertContains('mogilev-city', $citySlugs);
        self::assertGreaterThanOrEqual(10, count($citySlugs));
    }

    public function testRowsHaveValidEnumsAndCoordinates(): void
    {
        $addresses = [];

        foreach (AdminNationwideListingSeedDefinitions::all() as $row) {
            self::assertNotNull(DealType::tryFrom($row['dealType']));
            self::assertNotNull(ListingType::tryFrom($row['listingType']));
            self::assertGreaterThan(0, $row['price']);
            self::assertGreaterThan(0, $row['rooms']);
            self::assertGreaterThan(0.0, $row['area']);
            self::assertNotSame('', $row['address']);
            self::assertIsFloat($row['latOffset']);
            self::assertIsFloat($row['lngOffset']);

            if ($row['dealType'] === DealType::Rent->value) {
                self::assertIsString($row['rentTerm']);
                self::assertNotNull(RentTerm::tryFrom($row['rentTerm']));
            } else {
                self::assertNull($row['rentTerm']);
            }

            $key = $row['citySlug'] . '|' . $row['address'];
            self::assertArrayNotHasKey($key, $addresses);
            $addresses[$key] = true;
        }
    }
}
