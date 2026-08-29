<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Telegram\SellerTelegramStatusResponse;
use App\Entity\User;
use App\Entity\UserTelegramLink;
use App\Factory\UserTelegramLinkFactory;
use App\Repository\UserRepository;
use App\Repository\UserTelegramLinkRepository;
use App\Telegram\TelegramBotClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SellerTelegramService
{
    private const LINK_TTL_SECONDS = 3600;

    public function __construct(
        private readonly UserTelegramLinkRepository $linkRepository,
        private readonly UserRepository $userRepository,
        private readonly UserTelegramLinkFactory $linkFactory,
        private readonly TelegramBotClientInterface $botClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly string $botUsername,
        private readonly string $appSecret,
    ) {
    }

    public function getStatus(User $user): SellerTelegramStatusResponse
    {
        $link = $this->linkRepository->findOneByUser($user);
        $connected = $link instanceof UserTelegramLink && $link->isActive();

        return new SellerTelegramStatusResponse(
            configured: $this->botClient->isConfigured(),
            connected: $connected,
            botUsername: $this->getBotUsername(),
            connectUrl: $this->getConnectUrl($user),
            username: $connected ? $link->getUsername() : null,
            connectedAt: $connected ? $link->getConnectedAt()->format(\DateTimeInterface::ATOM) : null,
        );
    }

    public function getConnectUrl(User $user): string
    {
        $userId = $user->getId();
        if ($userId === null) {
            return 'https://t.me/' . $this->getBotUsername();
        }

        return 'https://t.me/' . $this->getBotUsername() . '?start=' . $this->createLinkToken($userId);
    }

    public function disconnect(User $user): SellerTelegramStatusResponse
    {
        $link = $this->linkRepository->findOneByUser($user);
        if ($link instanceof UserTelegramLink && $link->isActive()) {
            $link->setIsActive(false);
            $this->entityManager->flush();

            if ($this->botClient->isConfigured()) {
                try {
                    $this->botClient->sendMessage(
                        $link->getChatId(),
                        "Renlo: уведомления продавца отключены.\nЧтобы включить снова, откройте ссылку из личного кабинета."
                    );
                } catch (\Throwable $e) {
                    $this->logger->warning('Seller Telegram disconnect notify failed', [
                        'chatId' => $link->getChatId(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $this->getStatus($user);
    }

    /**
     * @return bool true when seller link payload was handled
     */
    public function tryBindFromStartPayload(
        string $payload,
        string $chatId,
        ?string $username,
        ?string $firstName,
    ): bool {
        $userId = $this->parseLinkToken($payload);
        if ($userId === null) {
            return false;
        }

        $user = $this->userRepository->find($userId);
        if (!$user instanceof User) {
            $this->botClient->sendMessage($chatId, 'Renlo: ссылка недействительна. Откройте новую из личного кабинета.');

            return true;
        }

        $this->bindUser($user, $chatId, $username, $firstName);
        $this->botClient->sendMessage(
            $chatId,
            "Renlo: уведомления продавца подключены.\nВы будете получать сообщения о статусе объявлений и заявках."
        );

        return true;
    }

    public function deactivateByChatId(string $chatId): bool
    {
        $link = $this->linkRepository->findOneByChatId($chatId);
        if (!$link instanceof UserTelegramLink) {
            return false;
        }

        $link->setIsActive(false);
        $this->entityManager->flush();

        return true;
    }

    public function notifyUser(User $user, string $message): void
    {
        if (!$this->botClient->isConfigured()) {
            return;
        }

        $link = $this->linkRepository->findActiveByUser($user);
        if (!$link instanceof UserTelegramLink) {
            return;
        }

        try {
            $this->botClient->sendMessage($link->getChatId(), $message);
        } catch (\Throwable $e) {
            $this->logger->warning('Seller Telegram notify failed', [
                'userId' => $user->getId(),
                'chatId' => $link->getChatId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function bindUser(User $user, string $chatId, ?string $username, ?string $firstName): void
    {
        $byChat = $this->linkRepository->findOneByChatId($chatId);
        if ($byChat instanceof UserTelegramLink && $byChat->getUser()?->getId() !== $user->getId()) {
            $this->entityManager->remove($byChat);
            $this->entityManager->flush();
        }

        $link = $this->linkRepository->findOneByUser($user);
        if ($link instanceof UserTelegramLink) {
            $link
                ->setChatId($chatId)
                ->setUsername($username)
                ->setFirstName($firstName)
                ->setIsActive(true)
                ->touchConnectedAt();
            $this->entityManager->flush();

            return;
        }

        $link = $this->linkFactory->create($user, $chatId, $username, $firstName, true);
        $this->entityManager->persist($link);
        $this->entityManager->flush();
    }

    private function createLinkToken(int $userId): string
    {
        $expiresAt = time() + self::LINK_TTL_SECONDS;
        $sig = substr(hash_hmac('sha256', $userId . ':' . $expiresAt, $this->appSecret), 0, 16);

        return 's' . $userId . '_' . $expiresAt . '_' . $sig;
    }

    private function parseLinkToken(string $payload): ?int
    {
        $payload = trim($payload);
        if (!preg_match('/^s(\d+)_(\d+)_([a-f0-9]{16})$/', $payload, $matches)) {
            return null;
        }

        $userId = (int) $matches[1];
        $expiresAt = (int) $matches[2];
        $sig = $matches[3];

        if ($expiresAt < time()) {
            return null;
        }

        $expected = substr(hash_hmac('sha256', $userId . ':' . $expiresAt, $this->appSecret), 0, 16);
        if (!hash_equals($expected, $sig)) {
            return null;
        }

        return $userId;
    }

    private function getBotUsername(): string
    {
        return $this->botUsername !== '' ? $this->botUsername : 'renlo_bot';
    }
}
