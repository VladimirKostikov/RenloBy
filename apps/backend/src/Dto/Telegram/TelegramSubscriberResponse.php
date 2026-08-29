<?php

declare(strict_types=1);

namespace App\Dto\Telegram;

readonly class TelegramSubscriberResponse
{
    public function __construct(
        public int $id,
        public string $chatId,
        public ?string $username,
        public ?string $firstName,
        public bool $isActive,
        public string $connectedAt,
    ) {
    }

    public static function fromEntity(\App\Entity\TelegramSubscriber $subscriber): self
    {
        return new self(
            $subscriber->getId() ?? 0,
            $subscriber->getChatId(),
            $subscriber->getUsername(),
            $subscriber->getFirstName(),
            $subscriber->isActive(),
            $subscriber->getConnectedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
