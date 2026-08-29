<?php

declare(strict_types=1);

namespace App\Payment\YooKassa;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class HttpYooKassaClient implements YooKassaClientInterface
{
    private const API_URL = 'https://api.yookassa.ru/v3/payments';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $shopId,
        private readonly string $secretKey,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->shopId !== '' && $this->secretKey !== '';
    }

    public function createPayment(
        string $amount,
        string $currency,
        string $description,
        string $returnUrl,
        array $metadata = [],
    ): YooKassaPaymentResult {
        $idempotenceKey = bin2hex(random_bytes(16));

        $response = $this->httpClient->request('POST', self::API_URL, [
            'auth_basic' => [$this->shopId, $this->secretKey],
            'headers' => [
                'Idempotence-Key' => $idempotenceKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'amount' => [
                    'value' => $amount,
                    'currency' => strtoupper($currency),
                ],
                'capture' => true,
                'confirmation' => [
                    'type' => 'redirect',
                    'return_url' => $returnUrl,
                ],
                'description' => mb_substr($description, 0, 128),
                'metadata' => $this->stringifyMetadata($metadata),
            ],
        ]);

        $statusCode = $response->getStatusCode();
        /** @var array<string, mixed> $payload */
        $payload = $response->toArray(false);

        if ($statusCode < 200 || $statusCode >= 300) {
            $this->logger->error('YooKassa createPayment failed', [
                'status' => $statusCode,
                'payload' => $payload,
            ]);

            throw new \RuntimeException('YooKassa payment create failed');
        }

        $confirmation = is_array($payload['confirmation'] ?? null) ? $payload['confirmation'] : [];
        $confirmationUrl = isset($confirmation['confirmation_url']) && is_string($confirmation['confirmation_url'])
            ? $confirmation['confirmation_url']
            : null;

        return new YooKassaPaymentResult(
            providerPaymentId: (string) ($payload['id'] ?? ''),
            status: (string) ($payload['status'] ?? 'pending'),
            confirmationUrl: $confirmationUrl,
            raw: $payload,
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
        $paymentId = trim($providerPaymentId);
        if ($paymentId === '' || !$this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', self::API_URL . '/' . rawurlencode($paymentId), [
                'auth_basic' => [$this->shopId, $this->secretKey],
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            /** @var array<string, mixed> $payload */
            $payload = $response->toArray(false);

            if ($statusCode < 200 || $statusCode >= 300) {
                $this->logger->warning('YooKassa fetchPayment failed', [
                    'status' => $statusCode,
                    'paymentId' => $paymentId,
                    'payload' => $payload,
                ]);

                return null;
            }

            $id = (string) ($payload['id'] ?? '');
            $status = (string) ($payload['status'] ?? '');
            if ($id === '' || $status === '') {
                return null;
            }

            return new YooKassaPaymentResult(
                providerPaymentId: $id,
                status: $status,
                confirmationUrl: null,
                raw: $payload,
            );
        } catch (\Throwable $e) {
            $this->logger->warning('YooKassa fetchPayment exception', [
                'paymentId' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function canVerifyPayments(): bool
    {
        return $this->isConfigured();
    }

    /**
     * @param array<string, mixed> $metadata
     *
     * @return array<string, string>
     */
    private function stringifyMetadata(array $metadata): array
    {
        $result = [];
        foreach ($metadata as $key => $value) {
            if (!is_string($key) || $key === '') {
                continue;
            }
            if (is_scalar($value) || $value === null) {
                $result[$key] = (string) $value;
            }
        }

        return $result;
    }
}
