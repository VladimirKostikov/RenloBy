<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\AuthCookieService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class AuthCookieServiceTest extends TestCase
{
    public function testCreateAndResolveUserId(): void
    {
        $service = new AuthCookieService('test-secret', 'test');
        $user = $this->createUser(42);

        $token = $service->createToken($user);

        self::assertSame(42, $service->resolveUserId($token));
        self::assertFalse($service->shouldRefresh($token));
    }

    public function testRejectsTamperedToken(): void
    {
        $service = new AuthCookieService('test-secret', 'test');
        $token = $service->createToken($this->createUser(7));

        self::assertNull($service->resolveUserId($token . 'x'));
    }

    public function testAttachTokenUsesNonSecureCookieOutsideProd(): void
    {
        $service = new AuthCookieService('test-secret', 'dev');
        $response = new Response();

        $service->attachToken($response, $service->createToken($this->createUser(1)));

        $cookie = $response->headers->getCookies()[0] ?? null;
        self::assertNotNull($cookie);
        self::assertSame(AuthCookieService::COOKIE_NAME, $cookie->getName());
        self::assertFalse($cookie->isSecure());
        self::assertTrue($cookie->isHttpOnly());
        self::assertEqualsWithDelta(
            time() + AuthCookieService::TTL_SECONDS,
            $cookie->getExpiresTime(),
            2,
        );
    }

    public function testAttachTokenUsesSecureCookieInProd(): void
    {
        $service = new AuthCookieService('test-secret', 'prod');
        $response = new Response();

        $service->attachToken($response, $service->createToken($this->createUser(1)));

        $cookie = $response->headers->getCookies()[0] ?? null;
        self::assertNotNull($cookie);
        self::assertTrue($cookie->isSecure());
    }

    public function testShouldRefreshWhenHalfLifePassed(): void
    {
        $service = new AuthCookieService('test-secret', 'test');
        $halfLifeAgo = time() - (int) floor(AuthCookieService::TTL_SECONDS / 2) - 10;
        $payload = base64_encode(json_encode([
            'uid' => 5,
            'exp' => $halfLifeAgo + AuthCookieService::TTL_SECONDS,
        ], JSON_THROW_ON_ERROR));
        $token = $payload . '.' . hash_hmac('sha256', $payload, 'test-secret');

        self::assertTrue($service->shouldRefresh($token));
        self::assertSame(5, $service->resolveUserId($token));
    }

    private function createUser(int $id): User
    {
        $user = new User();
        $reflection = new \ReflectionProperty(User::class, 'id');
        $reflection->setValue($user, $id);

        return $user;
    }
}
