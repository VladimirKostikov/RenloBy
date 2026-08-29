<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserTelegramLink;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserTelegramLink>
 */
class UserTelegramLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserTelegramLink::class);
    }

    public function findOneByUser(User $user): ?UserTelegramLink
    {
        return $this->findOneBy(['user' => $user]);
    }

    public function findOneByChatId(string $chatId): ?UserTelegramLink
    {
        return $this->findOneBy(['chatId' => $chatId]);
    }

    public function findActiveByUser(User $user): ?UserTelegramLink
    {
        return $this->findOneBy(['user' => $user, 'isActive' => true]);
    }
}
