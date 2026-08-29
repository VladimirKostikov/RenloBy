<?php

declare(strict_types=1);

namespace App\Payment\YooKassa;

final class ConfigurableYooKassaClient implements YooKassaClientInterface
{
    public function __construct(
        private readonly HttpYooKassaClient $httpClient,
        private readonly StubYooKassaClient $stubClient,
    ) {
    }

    public function createPayment(
        string $amount,
        string $currency,
        string $description,
        string $returnUrl,
        array $metadata = [],
    ): YooKassaPaymentResult {
        if ($this->httpClient->isConfigured()) {
            return $this->httpClient->createPayment($amount, $currency, $description, $returnUrl, $metadata);
        }

        return $this->stubClient->createPayment($amount, $currency, $description, $returnUrl, $metadata);
    }

    public function parseWebhookStatus(array $payload): ?YooKassaPaymentResult
    {
        if ($this->httpClient->isConfigured()) {
            return $this->httpClient->parseWebhookStatus($payload);
        }

        return $this->stubClient->parseWebhookStatus($payload);
    }

    public function fetchPayment(string $providerPaymentId): ?YooKassaPaymentResult
    {
        if ($this->httpClient->isConfigured()) {
            return $this->httpClient->fetchPayment($providerPaymentId);
        }

        return $this->stubClient->fetchPayment($providerPaymentId);
    }

    public function canVerifyPayments(): bool
    {
        return $this->httpClient->canVerifyPayments();
    }
}
