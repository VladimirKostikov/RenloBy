<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Telegram\TelegramSubscriberResponse;
use App\Entity\TelegramSubscriber;
use App\Entity\User;
use App\Repository\TelegramSubscriberRepository;
use App\Telegram\TelegramBotClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class TelegramNotificationService
{
    public function __construct(
        private readonly TelegramSubscriberRepository $subscriberRepository,
        private readonly TelegramBotClientInterface $botClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly SellerTelegramService $sellerTelegramService,
        private readonly string $botUsername,
    ) {
    }

    public function getBotUsername(): string
    {
        return $this->botUsername !== '' ? $this->botUsername : 'renlo_bot';
    }

    public function getConnectUrl(): string
    {
        return 'https://t.me/' . $this->getBotUsername() . '?start=connect';
    }

    public function isConfigured(): bool
    {
        return $this->botClient->isConfigured();
    }

    /**
     * @return array{
     *     configured: bool,
     *     botUsername: string,
     *     connectUrl: string,
     *     webhookUrl: string,
     *     webhookPendingUpdateCount: int,
     *     webhookLastError: string|null,
     *     subscribers: list<TelegramSubscriberResponse>
     * }
     */
    public function getStatus(): array
    {
        $webhook = $this->botClient->getWebhookInfo();

        return [
            'configured' => $this->isConfigured(),
            'botUsername' => $this->getBotUsername(),
            'connectUrl' => $this->getConnectUrl(),
            'webhookUrl' => $webhook['url'],
            'webhookPendingUpdateCount' => $webhook['pendingUpdateCount'],
            'webhookLastError' => $webhook['lastErrorMessage'],
            'subscribers' => $this->listSubscribers(),
        ];
    }

    /**
     * Pull pending Telegram updates via getUpdates.
     * Used when webhook is not reachable (local Docker / LAN).
     *
     * @return array{processed: int, connected: int}
     */
    public function syncPendingUpdates(): array
    {
        if (!$this->botClient->isConfigured()) {
            return ['processed' => 0, 'connected' => 0];
        }

        $webhook = $this->botClient->getWebhookInfo();
        if ($webhook['url'] !== '') {
            $this->logger->warning('Telegram sync skipped: webhook is set', [
                'webhookUrl' => $webhook['url'],
            ]);

            return ['processed' => 0, 'connected' => 0];
        }

        $updates = $this->botClient->getUpdates(0, 100, 0);
        $processed = 0;
        $connected = 0;
        $maxUpdateId = 0;

        foreach ($updates as $update) {
            $updateId = (int) ($update['update_id'] ?? 0);
            if ($updateId > $maxUpdateId) {
                $maxUpdateId = $updateId;
            }

            $message = is_array($update['message'] ?? null) ? $update['message'] : [];
            $text = trim((string) ($message['text'] ?? ''));
            if (str_starts_with($text, '/start')) {
                ++$connected;
            }

            $this->handleWebhookUpdate($update);
            ++$processed;
        }

        if ($maxUpdateId > 0) {
            $this->botClient->getUpdates($maxUpdateId + 1, 1, 0);
        }

        return [
            'processed' => $processed,
            'connected' => $connected,
        ];
    }

    /**
     * @return list<TelegramSubscriberResponse>
     */
    public function listSubscribers(): array
    {
        return array_map(
            static fn (TelegramSubscriber $s) => TelegramSubscriberResponse::fromEntity($s),
            $this->subscriberRepository->findBy([], ['connectedAt' => 'DESC'])
        );
    }

    public function setActive(int $id, bool $active): TelegramSubscriberResponse
    {
        $subscriber = $this->subscriberRepository->find($id);
        if (!$subscriber instanceof TelegramSubscriber) {
            throw new \App\Exception\ResourceNotFoundException(\App\Http\ApiErrorCode::NOT_FOUND_TELEGRAM_SUBSCRIBER);
        }

        $subscriber->setIsActive($active);
        $this->entityManager->flush();

        return TelegramSubscriberResponse::fromEntity($subscriber);
    }

    public function deleteSubscriber(int $id): void
    {
        $subscriber = $this->subscriberRepository->find($id);
        if (!$subscriber instanceof TelegramSubscriber) {
            throw new \App\Exception\ResourceNotFoundException(\App\Http\ApiErrorCode::NOT_FOUND_TELEGRAM_SUBSCRIBER);
        }

        $this->entityManager->remove($subscriber);
        $this->entityManager->flush();
    }

    /**
     * @param array<string, mixed> $update
     */
    public function handleWebhookUpdate(array $update): void
    {
        $message = $update['message'] ?? null;
        if (!is_array($message)) {
            return;
        }

        $chat = $message['chat'] ?? null;
        if (!is_array($chat)) {
            return;
        }

        $chatId = (string) ($chat['id'] ?? '');
        if ($chatId === '') {
            return;
        }

        $text = trim((string) ($message['text'] ?? ''));
        $from = is_array($message['from'] ?? null) ? $message['from'] : [];

        if (str_starts_with($text, '/start')) {
            $payload = trim(substr($text, strlen('/start')));
            $username = isset($from['username']) ? (string) $from['username'] : null;
            $firstName = isset($from['first_name']) ? (string) $from['first_name'] : null;

            if ($payload !== '' && $this->sellerTelegramService->tryBindFromStartPayload(
                $payload,
                $chatId,
                $username,
                $firstName,
            )) {
                return;
            }

            $this->connectChat($chatId, $username, $firstName);
            $this->botClient->sendMessage(
                $chatId,
                "Renlo: уведомления подключены.\nВы будете получать сообщения о регистрациях и оплатах тарифов."
            );

            return;
        }

        if ($text === '/stop') {
            $sellerStopped = $this->sellerTelegramService->deactivateByChatId($chatId);
            $existing = $this->subscriberRepository->findOneByChatId($chatId);
            if ($existing instanceof TelegramSubscriber) {
                $existing->setIsActive(false);
                $this->entityManager->flush();
            }
            if ($sellerStopped || $existing instanceof TelegramSubscriber) {
                $this->botClient->sendMessage(
                    $chatId,
                    'Renlo: уведомления отключены. Для подключения снова откройте ссылку из личного кабинета или отправьте /start'
                );
            }
        }
    }

    public function notifyRegistration(User $user): void
    {
        $this->notifyAll(
            "Новая регистрация\n"
            . 'Email: ' . $user->getEmail() . "\n"
            . 'Имя: ' . $user->getName() . "\n"
            . 'ID: ' . ($user->getId() ?? 0)
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function notifyTariffPurchase(
        User $user,
        string $amount,
        string $currency,
        array $metadata = [],
    ): void {
        $tariff = isset($metadata['tariffId']) ? (string) $metadata['tariffId'] : '-';
        $this->notifyAll(
            "Оплата тарифа\n"
            . 'Тариф: ' . $tariff . "\n"
            . 'Сумма: ' . $amount . ' ' . $currency . "\n"
            . 'Email: ' . $user->getEmail() . "\n"
            . 'ID пользователя: ' . ($user->getId() ?? 0)
        );
    }

    public function notifyAll(string $message): void
    {
        if (!$this->botClient->isConfigured()) {
            return;
        }

        foreach ($this->subscriberRepository->findActive() as $subscriber) {
            try {
                $this->botClient->sendMessage($subscriber->getChatId(), $message);
            } catch (\Throwable $e) {
                $this->logger->warning('Telegram notify failed', [
                    'chatId' => $subscriber->getChatId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function connectChat(string $chatId, ?string $username, ?string $firstName): void
    {
        $existing = $this->subscriberRepository->findOneByChatId($chatId);
        if ($existing instanceof TelegramSubscriber) {
            $existing->setIsActive(true);
            $existing->setUsername($username);
            $existing->setFirstName($firstName);
            $this->entityManager->flush();

            return;
        }

        $subscriber = (new TelegramSubscriber())
            ->setChatId($chatId)
            ->setUsername($username)
            ->setFirstName($firstName)
            ->setIsActive(true);

        $this->entityManager->persist($subscriber);
        $this->entityManager->flush();
    }
}
