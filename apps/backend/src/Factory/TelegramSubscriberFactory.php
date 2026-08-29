<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\TelegramSubscriber;

class TelegramSubscriberFactory
{
    public function create(
        string $chatId,
        ?string $username = null,
        ?string $firstName = null,
        bool $isActive = true,
    ): TelegramSubscriber {
        return (new TelegramSubscriber())
            ->setChatId($chatId)
            ->setUsername($username)
            ->setFirstName($firstName)
            ->setIsActive($isActive);
    }
}
