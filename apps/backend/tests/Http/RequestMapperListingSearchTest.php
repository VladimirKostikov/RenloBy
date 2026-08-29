<?php

declare(strict_types=1);

namespace App\Tests\Http;

use App\Http\RequestMapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class RequestMapperListingSearchTest extends TestCase
{
    public function testMapsExtendedListingSearchFilters(): void
    {
        $mapper = new RequestMapper();
        $request = Request::create('/api/listings', 'GET', [
            'dealType' => 'rent',
            'minPrice' => '200',
            'maxPrice' => '1000',
            'maxArea' => '150',
            'floor' => '7',
            'verified' => 'true',
            'rentTerm' => 'daily',
            'hasDeposit' => 'true',
            'utilitiesIncluded' => 'true',
            'noCommission' => 'true',
            'query' => 'Михалово',
        ]);

        $dto = $mapper->mapListingSearch($request);

        self::assertSame(200, $dto->minPrice);
        self::assertSame(1000, $dto->maxPrice);
        self::assertSame(150.0, $dto->maxArea);
        self::assertSame(7, $dto->floor);
        self::assertTrue($dto->verified);
        self::assertSame('daily', $dto->rentTerm?->value);
        self::assertTrue($dto->hasDeposit);
        self::assertTrue($dto->utilitiesIncluded);
        self::assertTrue($dto->noCommission);
        self::assertSame('Михалово', $dto->query);
    }

    public function testMapsRegionSlugFilter(): void
    {
        $mapper = new RequestMapper();
        $request = Request::create('/api/listings', 'GET', [
            'regionSlug' => 'minsk-region',
        ]);

        $dto = $mapper->mapListingSearch($request);

        self::assertSame('minsk-region', $dto->regionSlug);
    }

    public function testMapsSaleListingSearchFilters(): void
    {
        $mapper = new RequestMapper();
        $request = Request::create('/api/listings', 'GET', [
            'dealType' => 'sale',
            'fromOwner' => 'true',
            'hasRenovation' => 'true',
            'noCommission' => 'true',
        ]);

        $dto = $mapper->mapListingSearch($request);

        self::assertTrue($dto->fromOwner);
        self::assertTrue($dto->hasRenovation);
        self::assertTrue($dto->noCommission);
    }

    public function testMapsRandomSort(): void
    {
        $mapper = new RequestMapper();
        $request = Request::create('/api/listings', 'GET', [
            'sort' => 'random',
        ]);

        $dto = $mapper->mapListingSearch($request);

        self::assertSame('random', $dto->sort);
    }

    public function testRejectsUnknownSortToPublishedAt(): void
    {
        $mapper = new RequestMapper();
        $request = Request::create('/api/listings', 'GET', [
            'sort' => 'DROP TABLE listings',
        ]);

        $dto = $mapper->mapListingSearch($request);

        self::assertSame('publishedAt', $dto->sort);
    }
}
