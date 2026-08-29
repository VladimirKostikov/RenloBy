<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\GuestSessionCookieService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class GuestSessionCookieServiceTest extends TestCase
{
    public function testCreateAndResolveSessionHash(): void
    {
        $service = new GuestSessionCookieService('test-secret', 'test');
        $request = new Request();

        $session = $service->resolveOrCreate($request);
        self::assertTrue($session['isNew']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $session['hash']);

        $request = new Request(cookies: [
            GuestSessionCookieService::COOKIE_NAME => $session['token'],
        ]);

        self::assertSame($session['hash'], $service->resolveFromRequest($request));
    }
}
