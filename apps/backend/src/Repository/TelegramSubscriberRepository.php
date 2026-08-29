<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TelegramSubscriber;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TelegramSubscriberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramSubscriber::class);
    }

    /**
     * @return list<TelegramSubscriber>
     */
    public function findActive(): array
    {
        return $this->findBy(['isActive' => true], ['connectedAt' => 'DESC']);
    }

    public function findOneByChatId(string $chatId): ?TelegramSubscriber
    {
        return $this->findOneBy(['chatId' => $chatId]);
    }
}
