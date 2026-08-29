<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserPresenceService
{
    private const TOUCH_INTERVAL_SECONDS = 300;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function touch(User $user, bool $force = false): void
    {
        $now = new \DateTimeImmutable();
        $previous = $user->getLastSeenAt();

        if (!$force && $previous instanceof \DateTimeImmutable) {
            $elapsed = $now->getTimestamp() - $previous->getTimestamp();
            if ($elapsed < self::TOUCH_INTERVAL_SECONDS) {
                return;
            }
        }

        $user->setLastSeenAt($now);
        $this->entityManager->flush();
    }
}
