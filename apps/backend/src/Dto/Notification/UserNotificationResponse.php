<?php

declare(strict_types=1);

namespace App\Dto\Notification;

use App\Entity\UserNotification;

readonly class UserNotificationResponse
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public int $id,
        public string $type,
        public array $payload,
        public bool $isRead,
        public string $createdAt,
        public bool $isTest,
        public ?int $userId = null,
    ) {
    }

    public static function fromEntity(UserNotification $notification, bool $includeUserId = false): self
    {
        return new self(
            id: $notification->getId() ?? 0,
            type: $notification->getType()->value,
            payload: $notification->getPayload(),
            isRead: $notification->isRead(),
            createdAt: $notification->getCreatedAt()->format(\DateTimeInterface::ATOM),
            isTest: $notification->isTest(),
            userId: $includeUserId ? ($notification->getUser()?->getId()) : null,
        );
    }
}
