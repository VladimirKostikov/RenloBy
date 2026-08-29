<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Entity\UserTelegramLink;
use App\Factory\UserTelegramLinkFactory;
use App\Repository\UserRepository;
use App\Repository\UserTelegramLinkRepository;
use App\Service\SellerTelegramService;
use App\Tests\Telegram\InMemoryTelegramBotClient;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionClass;

final class SellerTelegramServiceTest extends TestCase
{
    public function testGetStatusDisconnectedProvidesConnectUrl(): void
    {
        $user = $this->userWithId(42);
        $repo = $this->createMock(UserTelegramLinkRepository::class);
        $repo->method('findOneByUser')->willReturn(null);

        $service = $this->createService($repo);
        $status = $service->getStatus($user);

        self::assertFalse($status->connected);
        self::assertTrue($status->configured);
        self::assertStringContainsString('t.me/renlo_bot?start=s42_', $status->connectUrl);
    }

    public function testBindFromValidStartPayload(): void
    {
        $user = $this->userWithId(7);
        $repo = $this->createMock(UserTelegramLinkRepository::class);
        $repo->method('findOneByChatId')->willReturn(null);
        $repo->method('findOneByUser')->willReturn(null);

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('find')->with(7)->willReturn($user);

        $bot = new InMemoryTelegramBotClient(true);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist');
        $em->expects(self::atLeastOnce())->method('flush');

        $service = new SellerTelegramService(
            $repo,
            $userRepo,
            new UserTelegramLinkFactory(),
            $bot,
            $em,
            new NullLogger(),
            'renlo_bot',
            'test-secret',
        );

        $token = $this->extractToken($service->getConnectUrl($user));
        self::assertTrue($service->tryBindFromStartPayload($token, '999', 'seller', 'Seller'));
        self::assertNotEmpty($bot->sent);
        self::assertStringContainsString('продавца подключены', $bot->sent[0]['text']);
    }

    public function testRejectsExpiredOrInvalidToken(): void
    {
        $repo = $this->createMock(UserTelegramLinkRepository::class);
        $service = $this->createService($repo);

        self::assertFalse($service->tryBindFromStartPayload('s1_1_abcdef0123456789', '1', null, null));
        self::assertFalse($service->tryBindFromStartPayload('connect', '1', null, null));
    }

    public function testDisconnectDeactivatesActiveLink(): void
    {
        $user = $this->userWithId(5);
        $link = (new UserTelegramLink())
            ->setUser($user)
            ->setChatId('321')
            ->setUsername('seller')
            ->setIsActive(true);

        $repo = $this->createMock(UserTelegramLinkRepository::class);
        $repo->method('findOneByUser')->willReturn($link);

        $bot = new InMemoryTelegramBotClient(true);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $service = new SellerTelegramService(
            $repo,
            $this->createMock(UserRepository::class),
            new UserTelegramLinkFactory(),
            $bot,
            $em,
            new NullLogger(),
            'renlo_bot',
            'test-secret',
        );

        $status = $service->disconnect($user);
        self::assertFalse($link->isActive());
        self::assertFalse($status->connected);
        self::assertStringContainsString('отключены', $bot->sent[0]['text']);
    }

    private function createService(UserTelegramLinkRepository $repo): SellerTelegramService
    {
        return new SellerTelegramService(
            $repo,
            $this->createMock(UserRepository::class),
            new UserTelegramLinkFactory(),
            new InMemoryTelegramBotClient(true),
            $this->createMock(EntityManagerInterface::class),
            new NullLogger(),
            'renlo_bot',
            'test-secret',
        );
    }

    private function userWithId(int $id): User
    {
        $user = (new User())
            ->setEmail('seller' . $id . '@renlo.local')
            ->setName('Seller')
            ->setPassword('hash');

        $ref = new ReflectionClass($user);
        $prop = $ref->getProperty('id');
        $prop->setValue($user, $id);

        return $user;
    }

    private function extractToken(string $url): string
    {
        $query = parse_url($url, PHP_URL_QUERY);
        self::assertIsString($query);
        parse_str($query, $params);
        self::assertArrayHasKey('start', $params);

        return (string) $params['start'];
    }
}
