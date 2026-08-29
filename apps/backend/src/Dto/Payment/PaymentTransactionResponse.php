<?php

declare(strict_types=1);

namespace App\Dto\Payment;

use App\Entity\PaymentTransaction;

readonly class PaymentTransactionResponse
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $amount,
        public string $currency,
        public string $status,
        public string $provider,
        public ?string $providerPaymentId,
        public ?string $description,
        public ?string $confirmationUrl,
        public array $metadata,
        public bool $isTest,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(PaymentTransaction $tx): self
    {
        return new self(
            $tx->getId() ?? 0,
            $tx->getUser()?->getId() ?? 0,
            $tx->getAmount(),
            $tx->getCurrency(),
            $tx->getStatus()->value,
            $tx->getProvider()->value,
            $tx->getProviderPaymentId(),
            $tx->getDescription(),
            $tx->getConfirmationUrl(),
            $tx->getMetadata(),
            $tx->isTest(),
            $tx->getCreatedAt()->format(\DateTimeInterface::ATOM),
            $tx->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
