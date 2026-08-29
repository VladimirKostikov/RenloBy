<?php

declare(strict_types=1);

namespace App\Tests\Payment;

use App\Payment\YooKassa\StubYooKassaClient;
use PHPUnit\Framework\TestCase;

final class StubYooKassaClientTest extends TestCase
{
    public function testCreatePaymentReturnsPendingStub(): void
    {
        $client = new StubYooKassaClient();
        $result = $client->createPayment('100.00', 'RUB', 'Promo', 'https://renlo.local/return');

        self::assertStringStartsWith('stub_', $result->providerPaymentId);
        self::assertSame('pending', $result->status);
        self::assertNotNull($result->confirmationUrl);
        self::assertStringContainsString('paymentId=', (string) $result->confirmationUrl);
    }

    public function testParseWebhookStatusReadsObject(): void
    {
        $client = new StubYooKassaClient();
        $result = $client->parseWebhookStatus([
            'object' => [
                'id' => 'stub_abc',
                'status' => 'succeeded',
            ],
        ]);

        self::assertNotNull($result);
        self::assertSame('stub_abc', $result->providerPaymentId);
        self::assertSame('succeeded', $result->status);
    }
}
