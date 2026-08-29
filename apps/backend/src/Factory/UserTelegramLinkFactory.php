<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\User;
use App\Entity\UserTelegramLink;

class UserTelegramLinkFactory
{
    public function create(
        User $user,
        string $chatId,
        ?string $username = null,
        ?string $firstName = null,
        bool $isActive = true,
    ): UserTelegramLink {
        return (new UserTelegramLink())
            ->setUser($user)
            ->setChatId($chatId)
            ->setUsername($username)
            ->setFirstName($firstName)
            ->setIsActive($isActive);
    }
}
