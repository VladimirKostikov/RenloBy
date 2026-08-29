<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PaymentTransaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaymentTransaction>
 */
class PaymentTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentTransaction::class);
    }

    /**
     * @return list<PaymentTransaction>
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['createdAt' => 'DESC']);
    }

    public function findOneByProviderPaymentId(string $providerPaymentId): ?PaymentTransaction
    {
        return $this->findOneBy(['providerPaymentId' => $providerPaymentId]);
    }
}
