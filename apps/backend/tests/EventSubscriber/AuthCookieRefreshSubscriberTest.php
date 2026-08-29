<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\AuthCookieRefreshSubscriber;
use App\Service\AuthCookieService;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class AuthCookieRefreshSubscriberTest extends TestCase
{
    public function testRefreshesCookieWhenHalfLifePassed(): void
    {
        $authCookieService = new AuthCookieService('test-secret', 'test');
        $user = $this->createUser(9);
        $oldPayload = base64_encode(json_encode([
            'uid' => 9,
            'exp' => time() + (int) floor(AuthCookieService::TTL_SECONDS / 2) - 60,
        ], JSON_THROW_ON_ERROR));
        $oldToken = $oldPayload . '.' . hash_hmac('sha256', $oldPayload, 'test-secret');

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $subscriber = new AuthCookieRefreshSubscriber($authCookieService, $security);
        $request = Request::create('/api/auth/me');
        $request->cookies->set(AuthCookieService::COOKIE_NAME, $oldToken);
        $response = new Response();

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );

        $subscriber->onKernelResponse($event);

        $cookies = $response->headers->getCookies();
        self::assertCount(1, $cookies);
        self::assertSame(AuthCookieService::COOKIE_NAME, $cookies[0]->getName());
        self::assertNotSame($oldToken, $cookies[0]->getValue());
        self::assertSame(9, $authCookieService->resolveUserId((string) $cookies[0]->getValue()));
    }

    public function testSkipsFreshToken(): void
    {
        $authCookieService = new AuthCookieService('test-secret', 'test');
        $user = $this->createUser(3);
        $token = $authCookieService->createToken($user);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $subscriber = new AuthCookieRefreshSubscriber($authCookieService, $security);
        $request = Request::create('/api/auth/me');
        $request->cookies->set(AuthCookieService::COOKIE_NAME, $token);
        $response = new Response();

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );

        $subscriber->onKernelResponse($event);

        self::assertSame([], $response->headers->getCookies());
    }

    private function createUser(int $id): User
    {
        $user = new User();
        $reflection = new \ReflectionProperty(User::class, 'id');
        $reflection->setValue($user, $id);

        return $user;
    }
}
