<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Article;
use App\Entity\City;
use PHPUnit\Framework\TestCase;

final class EntityTraitsTest extends TestCase
{
    public function testSoftDeleteMarksEntityAsDeleted(): void
    {
        $city = new City();

        self::assertFalse($city->isDeleted());
        self::assertNull($city->getDeletedAt());

        $city->softDelete();

        self::assertTrue($city->isDeleted());
        self::assertInstanceOf(\DateTimeImmutable::class, $city->getDeletedAt());
    }

    public function testRestoreClearsDeletedAt(): void
    {
        $city = new City();
        $city->softDelete();
        $city->restore();

        self::assertFalse($city->isDeleted());
        self::assertNull($city->getDeletedAt());
    }

    public function testIsTestDefaultsToFalseAndCanBeChanged(): void
    {
        $article = new Article();

        self::assertFalse($article->isTest());

        $article->setIsTest(true);

        self::assertTrue($article->isTest());
    }
}
