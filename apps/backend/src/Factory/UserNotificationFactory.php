<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\User;
use App\Entity\UserNotification;
use App\Enum\NotificationType;

class UserNotificationFactory
{
    /**
     * @param array<string, mixed> $payload
     */
    public function create(
        User $user,
        NotificationType $type = NotificationType::ListingStatusChanged,
        array $payload = [],
        bool $isTest = true,
        ?\DateTimeImmutable $readAt = null,
    ): UserNotification {
        return (new UserNotification())
            ->setUser($user)
            ->setType($type)
            ->setPayload($payload)
            ->setIsTest($isTest)
            ->setReadAt($readAt);
    }
}
