<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class AuthCookieService
{
    public const COOKIE_NAME = 'RENLO_TOKEN';
    public const TTL_SECONDS = 2592000;

    public function __construct(
        private readonly string $appSecret,
        private readonly string $appEnv,
    ) {
    }

    public function createToken(User $user): string
    {
        $payload = base64_encode(json_encode([
            'uid' => $user->getId(),
            'exp' => time() + self::TTL_SECONDS,
        ], JSON_THROW_ON_ERROR));

        $signature = hash_hmac('sha256', $payload, $this->appSecret);

        return $payload . '.' . $signature;
    }

    public function resolveUserId(string $token): ?int
    {
        $data = $this->decodePayload($token);
        if ($data === null) {
            return null;
        }

        return (int) $data['uid'];
    }

    public function shouldRefresh(string $token): bool
    {
        $data = $this->decodePayload($token);
        if ($data === null) {
            return false;
        }

        $remaining = (int) $data['exp'] - time();

        return $remaining < (int) floor(self::TTL_SECONDS / 2);
    }

    public function attachToken(Response $response, string $token): void
    {
        $response->headers->setCookie(
            Cookie::create(self::COOKIE_NAME)
                ->withValue($token)
                ->withExpires(new \DateTimeImmutable('+' . self::TTL_SECONDS . ' seconds'))
                ->withPath('/')
                ->withSecure($this->isSecure())
                ->withHttpOnly(true)
                ->withSameSite(Cookie::SAMESITE_LAX)
        );
    }

    public function clearToken(Response $response): void
    {
        $response->headers->clearCookie(
            self::COOKIE_NAME,
            '/',
            null,
            $this->isSecure(),
            true,
            Cookie::SAMESITE_LAX
        );
    }

    /**
     * @return array{uid: int|string, exp: int|string}|null
     */
    private function decodePayload(string $token): ?array
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$payload, $signature] = $parts;
        $expected = hash_hmac('sha256', $payload, $this->appSecret);
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $data = json_decode(base64_decode($payload, true) ?: '', true);
        if (!is_array($data) || !isset($data['uid'], $data['exp'])) {
            return null;
        }

        if ((int) $data['exp'] < time()) {
            return null;
        }

        return $data;
    }

    private function isSecure(): bool
    {
        return $this->appEnv === 'prod';
    }
}
