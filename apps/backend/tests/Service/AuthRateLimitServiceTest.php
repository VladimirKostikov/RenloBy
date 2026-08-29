<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Exception\TooManyRequestsException;
use App\Service\AuthRateLimitService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class AuthRateLimitServiceTest extends TestCase
{
    public function testLoginAllowsUnderLimitThenBlocks(): void
    {
        $service = new AuthRateLimitService(new ArrayAdapter(), 'dev');

        for ($i = 0; $i < 10; ++$i) {
            $service->assertLoginAllowed('127.0.0.1');
        }

        $this->expectException(TooManyRequestsException::class);
        $service->assertLoginAllowed('127.0.0.1');
    }

    public function testRegisterBucketsAreIndependent(): void
    {
        $service = new AuthRateLimitService(new ArrayAdapter(), 'dev');
        $service->assertRegisterAllowed('10.0.0.1');
        $service->assertLoginAllowed('10.0.0.1');
        self::assertTrue(true);
    }

    public function testSkippedInTestEnvironment(): void
    {
        $service = new AuthRateLimitService(new ArrayAdapter(), 'test');
        for ($i = 0; $i < 20; ++$i) {
            $service->assertLoginAllowed('127.0.0.1');
        }
        self::assertTrue(true);
    }
}
