<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TariffControllerTest extends WebTestCase
{
    private const ADMIN_EMAIL = 'admin@renlo.local';
    private const ADMIN_PASSWORD = 'Admin123!';

    public function testPublicCanListTariffs(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/tariffs');
        self::assertResponseIsSuccessful();

        $list = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($list);
        self::assertNotEmpty($list);
        self::assertArrayHasKey('code', $list[0]);
        self::assertArrayHasKey('priceUsd', $list[0]);
        self::assertArrayHasKey('priceByn', $list[0]);
        self::assertArrayHasKey('priceRub', $list[0]);
    }

    public function testAdminTariffsRequireAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/tariffs');
        self::assertResponseStatusCodeSame(401);
    }

    public function testAdminCanUpdateTariffPrice(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $client->request('GET', '/admin/tariffs', ['isTest' => '1']);
        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertNotEmpty($list);

        $id = (int) $list[0]['id'];
        $client->request(
            'PATCH',
            '/admin/tariffs/' . $id . '?isTest=1',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_ADMIN_TEST_MODE' => '1',
            ],
            json_encode([
                'priceUsd' => '123.50',
                'priceByn' => '400.00',
                'priceRub' => '11500.00',
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
        $updated = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('123.50', $updated['priceUsd']);
        self::assertSame('400.00', $updated['priceByn']);
        self::assertSame('11500.00', $updated['priceRub']);
    }

    public function testPaymentTransactionsAdminRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/payment-transactions');
        self::assertResponseStatusCodeSame(401);
    }

    public function testAdminCanListPaymentTransactions(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);
        $client->request('GET', '/admin/payment-transactions');
        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($list);
    }

    private function loginAsAdmin($client): void
    {
        $client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => self::ADMIN_EMAIL,
                'password' => self::ADMIN_PASSWORD,
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
    }
}
