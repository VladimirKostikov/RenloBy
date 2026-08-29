<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\PaymentTransaction;
use App\Entity\User;
use App\Enum\PaymentProvider;
use App\Enum\PaymentStatus;

class PaymentTransactionFactory
{
    public function create(
        User $user,
        string $amount = '100.00',
        string $currency = 'RUB',
        PaymentStatus $status = PaymentStatus::Pending,
        bool $isTest = true,
    ): PaymentTransaction {
        return (new PaymentTransaction())
            ->setUser($user)
            ->setAmount($amount)
            ->setCurrency($currency)
            ->setStatus($status)
            ->setProvider(PaymentProvider::YooKassa)
            ->setDescription('Test payment')
            ->setMetadata([])
            ->setIsTest($isTest);
    }
}
