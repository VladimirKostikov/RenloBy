<?php

declare(strict_types=1);

namespace App\Tests\Doctrine\Filter;

use App\Doctrine\Filter\TestDataFilter;
use App\Entity\City;
use App\Entity\District;
use App\Entity\HeadSnippet;
use App\Entity\Listing;
use App\Entity\MetroStation;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class TestDataFilterTest extends TestCase
{
    public function testParsesFalseWithoutTreatingEmptyStringAsInvalidSql(): void
    {
        self::assertFalse(TestDataFilter::parseIsTestParameter(''));
        self::assertFalse(TestDataFilter::parseIsTestParameter("''"));
        self::assertFalse(TestDataFilter::parseIsTestParameter("'0'"));
        self::assertFalse(TestDataFilter::parseIsTestParameter('0'));
        self::assertFalse(TestDataFilter::parseIsTestParameter("'false'"));
    }

    public function testParsesTrueFlags(): void
    {
        self::assertTrue(TestDataFilter::parseIsTestParameter("'1'"));
        self::assertTrue(TestDataFilter::parseIsTestParameter('1'));
        self::assertTrue(TestDataFilter::parseIsTestParameter("'true'"));
        self::assertTrue(TestDataFilter::parseIsTestParameter('true'));
    }

    public function testSkipsReferenceEntities(): void
    {
        self::assertTrue(TestDataFilter::shouldSkipEntity(User::class));
        self::assertTrue(TestDataFilter::shouldSkipEntity(City::class));
        self::assertTrue(TestDataFilter::shouldSkipEntity(District::class));
        self::assertTrue(TestDataFilter::shouldSkipEntity(MetroStation::class));
        self::assertTrue(TestDataFilter::shouldSkipEntity(HeadSnippet::class));
        self::assertFalse(TestDataFilter::shouldSkipEntity(Listing::class));
    }
}
