<?php

declare(strict_types=1);

namespace App\Tests\Http;

use App\Http\RequestMapper;
use PHPUnit\Framework\TestCase;

final class RequestMapperIsTestTest extends TestCase
{
    public function testMapCreateCityReadsOptionalIsTest(): void
    {
        $mapper = new RequestMapper();

        $request = $mapper->mapCreateCity([
            'name' => 'City',
            'slug' => 'city',
            'isTest' => true,
        ]);

        self::assertTrue($request->isTest);
    }

    public function testMapUpdateCityLeavesIsTestNullWhenMissing(): void
    {
        $mapper = new RequestMapper();

        $request = $mapper->mapUpdateCity([
            'name' => 'Updated',
        ]);

        self::assertNull($request->isTest);
    }
}
