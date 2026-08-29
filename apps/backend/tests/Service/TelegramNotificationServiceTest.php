<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\TelegramSubscriber;
use App\Entity\User;
use App\Repository\TelegramSubscriberRepository;
use App\Service\TelegramNotificationService;
use App\Tests\Telegram\InMemoryTelegramBotClient;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class TelegramNotificationServiceTest extends TestCase
{
    public function testNotifyRegistrationSendsToActiveSubscribers(): void
    {
        $active = (new TelegramSubscriber())
            ->setChatId('111')
            ->setIsActive(true);
        $inactive = (new TelegramSubscriber())
            ->setChatId('222')
            ->setIsActive(false);

        $repo = $this->createMock(TelegramSubscriberRepository::class);
        $repo->method('findActive')->willReturn([$active]);

        $bot = new InMemoryTelegramBotClient(true);
        $em = $this->createMock(EntityManagerInterface::class);

        $service = new TelegramNotificationService(
            $repo,
            $bot,
            $em,
            new NullLogger(),
            $this->createMock(\App\Service\SellerTelegramService::class),
            'renlo_bot',
        );

        $user = (new User())
            ->setEmail('new@renlo.local')
            ->setName('New User')
            ->setPassword('hash');

        $service->notifyRegistration($user);

        self::assertCount(1, $bot->sent);
        self::assertSame('111', $bot->sent[0]['chatId']);
        self::assertStringContainsString('new@renlo.local', $bot->sent[0]['text']);
        self::assertStringContainsString('Новая регистрация', $bot->sent[0]['text']);
    }

    public function testSyncPendingUpdatesConnectsFromGetUpdates(): void
    {
        $repo = $this->createMock(TelegramSubscriberRepository::class);
        $repo->method('findOneByChatId')->willReturn(null);

        $bot = new InMemoryTelegramBotClient(true);
        $bot->updates = [
            [
                'update_id' => 10,
                'message' => [
                    'chat' => ['id' => 555],
                    'from' => ['username' => 'boss', 'first_name' => 'Boss'],
                    'text' => '/start connect',
                ],
            ],
        ];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist');
        $em->expects(self::atLeastOnce())->method('flush');

        $service = new TelegramNotificationService(
            $repo,
            $bot,
            $em,
            new NullLogger(),
            $this->createMock(\App\Service\SellerTelegramService::class),
            'renlo_bot',
        );

        $result = $service->syncPendingUpdates();

        self::assertSame(1, $result['processed']);
        self::assertSame(1, $result['connected']);
        self::assertNotEmpty($bot->sent);
        self::assertStringContainsString('подключены', $bot->sent[0]['text']);
    }

    public function testGetStatusIncludesWebhookInfo(): void
    {
        $repo = $this->createMock(TelegramSubscriberRepository::class);
        $repo->method('findBy')->willReturn([]);

        $bot = new InMemoryTelegramBotClient(true);
        $bot->webhookInfo = [
            'url' => '',
            'pendingUpdateCount' => 3,
            'lastErrorMessage' => null,
        ];

        $service = new TelegramNotificationService(
            $repo,
            $bot,
            $this->createMock(EntityManagerInterface::class),
            new NullLogger(),
            $this->createMock(\App\Service\SellerTelegramService::class),
            'renlo_bot',
        );

        $status = $service->getStatus();
        self::assertTrue($status['configured']);
        self::assertSame('', $status['webhookUrl']);
        self::assertSame(3, $status['webhookPendingUpdateCount']);
    }
}
