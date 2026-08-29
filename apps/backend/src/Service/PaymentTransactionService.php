<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Payment\CreatePaymentRequest;
use App\Dto\Payment\PaymentTransactionResponse;
use App\Entity\PaymentTransaction;
use App\Entity\User;
use App\Enum\PaymentProvider;
use App\Enum\PaymentStatus;
use App\Exception\ResourceNotFoundException;
use App\Http\ApiErrorCode;
use App\Payment\YooKassa\YooKassaClientInterface;
use App\Repository\PaymentTransactionRepository;
use Doctrine\ORM\EntityManagerInterface;

class PaymentTransactionService
{
    public function __construct(
        private readonly PaymentTransactionRepository $paymentTransactionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly YooKassaClientInterface $yooKassaClient,
        private readonly TelegramNotificationService $telegramNotificationService,
        private readonly TariffService $tariffService,
        private readonly CurrencyConverter $currencyConverter,
    ) {
    }

    /**
     * @return list<PaymentTransactionResponse>
     */
    public function listForUser(User $user): array
    {
        return array_map(
            static fn (PaymentTransaction $tx) => PaymentTransactionResponse::fromEntity($tx),
            $this->paymentTransactionRepository->findByUser($user)
        );
    }

    /**
     * @return list<PaymentTransactionResponse>
     */
    public function listAll(): array
    {
        return array_map(
            static fn (PaymentTransaction $tx) => PaymentTransactionResponse::fromEntity($tx),
            $this->paymentTransactionRepository->findBy([], ['createdAt' => 'DESC'])
        );
    }

    public function get(int $id): PaymentTransactionResponse
    {
        return PaymentTransactionResponse::fromEntity($this->findEntity($id));
    }

    public function createForUser(User $user, CreatePaymentRequest $request): PaymentTransactionResponse
    {
        $metadata = $request->metadata;
        $tariffCode = isset($metadata['tariffId']) && is_string($metadata['tariffId'])
            ? trim($metadata['tariffId'])
            : '';
        $currency = $this->currencyConverter->assertSupported($request->currency);

        if ($tariffCode !== '') {
            $tariff = $this->tariffService->findActiveByCode($tariffCode, $request->isTest);
            $amount = $this->tariffService->amountForCurrency($tariff, $currency);
            $description = trim($request->description);
            if ($description === '') {
                $description = 'Tariff ' . $tariff->getCode();
            }
            $metadata['tariffId'] = $tariff->getCode();
            $metadata['priceUsd'] = $tariff->getPriceUsd();
            $metadata['priceByn'] = $tariff->getPriceByn();
            $metadata['priceRub'] = $tariff->getPriceRub();
        } else {
            $amount = $this->normalizeAmount($request->amount);
            $description = trim($request->description);
        }

        $returnUrl = trim($request->returnUrl);

        $result = $this->yooKassaClient->createPayment(
            $amount,
            $currency,
            $description,
            $returnUrl,
            $metadata,
        );

        $tx = (new PaymentTransaction())
            ->setUser($user)
            ->setAmount($amount)
            ->setCurrency($currency)
            ->setStatus($this->mapStatus($result->status))
            ->setProvider(PaymentProvider::YooKassa)
            ->setProviderPaymentId($result->providerPaymentId)
            ->setDescription($description !== '' ? $description : null)
            ->setConfirmationUrl($result->confirmationUrl)
            ->setMetadata(array_merge($metadata, ['provider_raw' => $result->raw]))
            ->setIsTest($request->isTest);

        $this->entityManager->persist($tx);
        $this->entityManager->flush();

        return PaymentTransactionResponse::fromEntity($tx);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handleYooKassaWebhook(array $payload): ?PaymentTransactionResponse
    {
        $hint = $this->yooKassaClient->parseWebhookStatus($payload);
        if ($hint === null) {
            return null;
        }

        $result = $hint;
        if ($this->yooKassaClient->canVerifyPayments()) {
            $verified = $this->yooKassaClient->fetchPayment($hint->providerPaymentId);
            if ($verified === null) {
                return null;
            }
            $result = $verified;
        }

        $tx = $this->paymentTransactionRepository->findOneByProviderPaymentId($result->providerPaymentId);
        if (!$tx instanceof PaymentTransaction) {
            return null;
        }

        $previous = $tx->getStatus();
        $tx->setStatus($this->mapStatus($result->status));
        $metadata = $tx->getMetadata();
        $metadata['last_webhook'] = $payload;
        $metadata['verified_status'] = $result->raw;
        $tx->setMetadata($metadata);
        $this->entityManager->flush();

        if (
            $tx->getStatus() === PaymentStatus::Succeeded
            && $previous !== PaymentStatus::Succeeded
            && $tx->getUser() instanceof User
        ) {
            $this->telegramNotificationService->notifyTariffPurchase(
                $tx->getUser(),
                $tx->getAmount(),
                $tx->getCurrency(),
                $tx->getMetadata(),
            );
        }

        return PaymentTransactionResponse::fromEntity($tx);
    }

    public function delete(int $id): void
    {
        $tx = $this->findEntity($id);
        $tx->softDelete();
        $this->entityManager->flush();
    }

    public function findEntity(int $id): PaymentTransaction
    {
        $tx = $this->paymentTransactionRepository->find($id);
        if (!$tx instanceof PaymentTransaction) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_PAYMENT_TRANSACTION);
        }

        return $tx;
    }

    private function normalizeAmount(string $amount): string
    {
        $normalized = number_format((float) str_replace(',', '.', $amount), 2, '.', '');
        if ((float) $normalized <= 0) {
            return '0.01';
        }

        return $normalized;
    }

    private function mapStatus(string $status): PaymentStatus
    {
        return match ($status) {
            'waiting_for_capture' => PaymentStatus::WaitingForCapture,
            'succeeded' => PaymentStatus::Succeeded,
            'canceled' => PaymentStatus::Canceled,
            default => PaymentStatus::Pending,
        };
    }
}
