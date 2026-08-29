<?php

declare(strict_types=1);

namespace App\Payment\YooKassa;

/**
 * Stub client used until live YooKassa credentials are configured.
 * Creates a deterministic pending payment with a placeholder confirmation URL.
 */
final class StubYooKassaClient implements YooKassaClientInterface
{
    public function createPayment(
        string $amount,
        string $currency,
        string $description,
        string $returnUrl,
        array $metadata = [],
    ): YooKassaPaymentResult {
        $id = 'stub_' . bin2hex(random_bytes(8));

        return new YooKassaPaymentResult(
            providerPaymentId: $id,
            status: 'pending',
            confirmationUrl: $returnUrl . (str_contains($returnUrl, '?') ? '&' : '?') . 'paymentId=' . urlencode($id),
            raw: [
                'stub' => true,
                'amount' => $amount,
                'currency' => $currency,
                'description' => $description,
                'metadata' => $metadata,
            ],
        );
    }

    public function parseWebhookStatus(array $payload): ?YooKassaPaymentResult
    {
        $object = $payload['object'] ?? null;
        if (!is_array($object)) {
            return null;
        }

        $id = (string) ($object['id'] ?? '');
        $status = (string) ($object['status'] ?? '');
        if ($id === '' || $status === '') {
            return null;
        }

        return new YooKassaPaymentResult(
            providerPaymentId: $id,
            status: $status,
            confirmationUrl: null,
            raw: $payload,
        );
    }

    public function fetchPayment(string $providerPaymentId): ?YooKassaPaymentResult
    {
        return null;
    }

    public function canVerifyPayments(): bool
    {
        return false;
    }
}
