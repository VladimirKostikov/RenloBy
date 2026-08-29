<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\TooManyRequestsException;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class AuthRateLimitService
{
    private const LOGIN_MAX = 10;
    private const LOGIN_WINDOW = 300;
    private const REGISTER_MAX = 5;
    private const REGISTER_WINDOW = 600;

    public function __construct(
        #[Autowire(service: 'cache.app')]
        private readonly CacheItemPoolInterface $cache,
        #[Autowire('%kernel.environment%')]
        private readonly string $appEnv = 'prod',
    ) {
    }

    public function assertLoginAllowed(string $clientKey): void
    {
        $this->assertAllowed('login:' . $clientKey, self::LOGIN_MAX, self::LOGIN_WINDOW);
    }

    public function assertRegisterAllowed(string $clientKey): void
    {
        $this->assertAllowed('register:' . $clientKey, self::REGISTER_MAX, self::REGISTER_WINDOW);
    }

    private function assertAllowed(string $bucket, int $maxAttempts, int $windowSeconds): void
    {
        if ($this->appEnv === 'test') {
            return;
        }

        $cacheKey = 'auth_rl_' . hash('sha256', $bucket);
        $now = time();
        $item = $this->cache->getItem($cacheKey);

        /** @var array{count: int, resetAt: int}|null $raw */
        $raw = $item->isHit() ? $item->get() : null;
        $state = is_array($raw) && isset($raw['count'], $raw['resetAt'])
            ? ['count' => (int) $raw['count'], 'resetAt' => (int) $raw['resetAt']]
            : ['count' => 0, 'resetAt' => $now + $windowSeconds];

        if ($state['resetAt'] <= $now) {
            $state = [
                'count' => 0,
                'resetAt' => $now + $windowSeconds,
            ];
        }

        $state['count']++;
        $retryAfter = max(1, $state['resetAt'] - $now);
        $item->set($state);
        $item->expiresAfter($retryAfter);
        $this->cache->save($item);

        if ($state['count'] > $maxAttempts) {
            throw new TooManyRequestsException('auth.rate_limited', $retryAfter);
        }
    }
}
