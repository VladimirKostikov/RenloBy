<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\PaymentTransaction;
use App\Entity\User;
use App\Enum\PaymentProvider;
use App\Enum\PaymentStatus;
use App\Payment\YooKassa\YooKassaClientInterface;
use App\Payment\YooKassa\YooKassaPaymentResult;
use App\Repository\PaymentTransactionRepository;
use App\Service\CurrencyConverter;
use App\Service\PaymentTransactionService;
use App\Service\TariffService;
use App\Service\TelegramNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class PaymentTransactionWebhookSecurityTest extends TestCase
{
    public function testIgnoresWebhookWhenLiveVerificationFails(): void
    {
        $client = $this->createMock(YooKassaClientInterface::class);
        $client->method('parseWebhookStatus')->willReturn(new YooKassaPaymentResult(
            providerPaymentId: 'pay_1',
            status: 'succeeded',
            confirmationUrl: null,
            raw: ['object' => ['id' => 'pay_1', 'status' => 'succeeded']],
        ));
        $client->method('canVerifyPayments')->willReturn(true);
        $client->method('fetchPayment')->willReturn(null);

        $repo = $this->createMock(PaymentTransactionRepository::class);
        $repo->expects(self::never())->method('findOneByProviderPaymentId');

        $service = new PaymentTransactionService(
            $repo,
            $this->createMock(EntityManagerInterface::class),
            $client,
            $this->createMock(TelegramNotificationService::class),
            $this->createMock(TariffService::class),
            new CurrencyConverter(3.27, 93),
        );

        self::assertNull($service->handleYooKassaWebhook([
            'object' => ['id' => 'pay_1', 'status' => 'succeeded'],
        ]));
    }

    public function testUsesVerifiedStatusFromApi(): void
    {
        $client = $this->createMock(YooKassaClientInterface::class);
        $client->method('parseWebhookStatus')->willReturn(new YooKassaPaymentResult(
            providerPaymentId: 'pay_2',
            status: 'succeeded',
            confirmationUrl: null,
            raw: ['forged' => true],
        ));
        $client->method('canVerifyPayments')->willReturn(true);
        $client->method('fetchPayment')->willReturn(new YooKassaPaymentResult(
            providerPaymentId: 'pay_2',
            status: 'pending',
            confirmationUrl: null,
            raw: ['id' => 'pay_2', 'status' => 'pending'],
        ));

        $user = (new User())->setEmail('pay@renlo.local')->setPassword('x');
        $tx = (new PaymentTransaction())
            ->setUser($user)
            ->setAmount('10.00')
            ->setCurrency('RUB')
            ->setStatus(PaymentStatus::Pending)
            ->setProvider(PaymentProvider::YooKassa)
            ->setProviderPaymentId('pay_2')
            ->setIsTest(true);

        $repo = $this->createMock(PaymentTransactionRepository::class);
        $repo->method('findOneByProviderPaymentId')->with('pay_2')->willReturn($tx);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $telegram = $this->createMock(TelegramNotificationService::class);
        $telegram->expects(self::never())->method('notifyTariffPurchase');

        $service = new PaymentTransactionService(
            $repo,
            $em,
            $client,
            $telegram,
            $this->createMock(TariffService::class),
            new CurrencyConverter(3.27, 93),
        );

        $response = $service->handleYooKassaWebhook([
            'object' => ['id' => 'pay_2', 'status' => 'succeeded'],
        ]);

        self::assertNotNull($response);
        self::assertSame(PaymentStatus::Pending, $tx->getStatus());
    }
}
