<?php

declare(strict_types=1);

namespace App\Payment\YooKassa;

interface YooKassaClientInterface
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function createPayment(
        string $amount,
        string $currency,
        string $description,
        string $returnUrl,
        array $metadata = [],
    ): YooKassaPaymentResult;

    /**
     * @param array<string, mixed> $payload
     */
    public function parseWebhookStatus(array $payload): ?YooKassaPaymentResult;

    public function fetchPayment(string $providerPaymentId): ?YooKassaPaymentResult;

    public function canVerifyPayments(): bool;
}
