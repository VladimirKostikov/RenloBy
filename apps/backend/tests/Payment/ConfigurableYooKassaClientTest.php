<?php

declare(strict_types=1);

namespace App\Tests\Payment;

use App\Payment\YooKassa\ConfigurableYooKassaClient;
use App\Payment\YooKassa\HttpYooKassaClient;
use App\Payment\YooKassa\StubYooKassaClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ConfigurableYooKassaClientTest extends TestCase
{
    public function testFallsBackToStubWithoutCredentials(): void
    {
        $http = new HttpYooKassaClient(new MockHttpClient(), new NullLogger(), '', '');
        $client = new ConfigurableYooKassaClient($http, new StubYooKassaClient());

        $result = $client->createPayment('19.90', 'BYN', 'Оптимум', 'https://renlo.by/account/seller/payments');

        self::assertStringStartsWith('stub_', $result->providerPaymentId);
        self::assertStringContainsString('paymentId=', (string) $result->confirmationUrl);
    }

    public function testUsesHttpClientWhenConfigured(): void
    {
        $mock = new MockHttpClient(static function (): MockResponse {
            return new MockResponse(json_encode([
                'id' => 'live_payment_1',
                'status' => 'pending',
                'confirmation' => ['confirmation_url' => 'https://yoomoney.ru/checkout/payments/v2/contract?orderId=1'],
            ], JSON_THROW_ON_ERROR));
        });

        $http = new HttpYooKassaClient($mock, new NullLogger(), 'shop', 'secret');
        $client = new ConfigurableYooKassaClient($http, new StubYooKassaClient());

        $result = $client->createPayment('65.00', 'BYN', 'Оптимум', 'https://renlo.by/account/seller/payments');

        self::assertSame('live_payment_1', $result->providerPaymentId);
        self::assertSame('https://yoomoney.ru/checkout/payments/v2/contract?orderId=1', $result->confirmationUrl);
    }

    public function testFetchPaymentUsesHttpWhenConfigured(): void
    {
        $mock = new MockHttpClient(static function (): MockResponse {
            return new MockResponse(json_encode([
                'id' => 'live_payment_1',
                'status' => 'succeeded',
            ], JSON_THROW_ON_ERROR));
        });

        $http = new HttpYooKassaClient($mock, new NullLogger(), 'shop', 'secret');
        $client = new ConfigurableYooKassaClient($http, new StubYooKassaClient());

        self::assertTrue($client->canVerifyPayments());
        $result = $client->fetchPayment('live_payment_1');
        self::assertNotNull($result);
        self::assertSame('succeeded', $result->status);
    }

    public function testFetchPaymentUnavailableInStubMode(): void
    {
        $http = new HttpYooKassaClient(new MockHttpClient(), new NullLogger(), '', '');
        $client = new ConfigurableYooKassaClient($http, new StubYooKassaClient());

        self::assertFalse($client->canVerifyPayments());
        self::assertNull($client->fetchPayment('stub_x'));
    }
}
