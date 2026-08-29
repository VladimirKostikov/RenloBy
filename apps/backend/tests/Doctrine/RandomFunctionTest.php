<?php

declare(strict_types=1);

namespace App\Tests\Doctrine;

use App\Doctrine\RandomFunction;
use Doctrine\ORM\Query\SqlWalker;
use PHPUnit\Framework\TestCase;

final class RandomFunctionTest extends TestCase
{
    public function testGetSqlReturnsPostgresRandom(): void
    {
        $function = new RandomFunction('random');
        $walker = new class extends SqlWalker {
            public function __construct()
            {
            }
        };

        self::assertSame('RANDOM()', $function->getSql($walker));
    }
}
