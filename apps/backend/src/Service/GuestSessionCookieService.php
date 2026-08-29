<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GuestSessionCookieService
{
    public const COOKIE_NAME = 'RENLO_GUEST_SESSION';
    private const TTL_SECONDS = 31536000;

    public function __construct(
        private readonly string $appSecret,
        private readonly string $appEnv,
    ) {
    }

    public function resolveFromRequest(Request $request): ?string
    {
        $token = $request->cookies->get(self::COOKIE_NAME);
        if (!is_string($token) || $token === '') {
            return null;
        }

        return $this->resolveSessionHash($token);
    }

    /**
     * @return array{hash: string, token: string, isNew: bool}
     */
    public function resolveOrCreate(Request $request): array
    {
        $existing = $this->resolveFromRequest($request);
        if ($existing !== null) {
            $token = $request->cookies->get(self::COOKIE_NAME);
            if (!is_string($token)) {
                $token = $this->createToken($existing);
            }

            return [
                'hash' => $existing,
                'token' => $token,
                'isNew' => false,
            ];
        }

        $hash = bin2hex(random_bytes(16));
        $token = $this->createToken($hash);

        return [
            'hash' => $hash,
            'token' => $token,
            'isNew' => true,
        ];
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

    private function createToken(string $sessionHash): string
    {
        $payload = base64_encode(json_encode([
            'sid' => $sessionHash,
            'exp' => time() + self::TTL_SECONDS,
        ], JSON_THROW_ON_ERROR));

        $signature = hash_hmac('sha256', $payload, $this->appSecret);

        return $payload . '.' . $signature;
    }

    private function resolveSessionHash(string $token): ?string
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
        if (!is_array($data) || !isset($data['sid'], $data['exp'])) {
            return null;
        }

        if ((int) $data['exp'] < time()) {
            return null;
        }

        $sessionHash = (string) $data['sid'];
        if ($sessionHash === '' || !preg_match('/^[a-f0-9]{32}$/', $sessionHash)) {
            return null;
        }

        return $sessionHash;
    }

    private function isSecure(): bool
    {
        return $this->appEnv === 'prod';
    }
}
