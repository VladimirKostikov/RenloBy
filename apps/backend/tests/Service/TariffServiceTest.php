<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\Tariff\UpdateTariffRequest;
use App\Factory\TariffFactory;
use App\Repository\TariffRepository;
use App\Service\TariffService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class TariffServiceTest extends TestCase
{
    public function testUpdateStoresAllCurrencyPrices(): void
    {
        $tariff = (new TariffFactory())->create(
            code: 'basic',
            priceUsd: '9.90',
            priceByn: '32.00',
            priceRub: '920.00',
        );

        $repository = $this->createMock(TariffRepository::class);
        $repository->method('find')->with(1)->willReturn($tariff);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $service = new TariffService($repository, $entityManager);
        $response = $service->update(1, new UpdateTariffRequest(
            priceUsd: '11.00',
            priceByn: '36.00',
            priceRub: '1020.00',
        ));

        self::assertSame('11.00', $response->priceUsd);
        self::assertSame('36.00', $response->priceByn);
        self::assertSame('1020.00', $response->priceRub);
        self::assertSame('11.00', $tariff->getPriceUsd());
        self::assertSame('36.00', $tariff->getPriceByn());
        self::assertSame('1020.00', $tariff->getPriceRub());
    }

    public function testAmountForCurrencyUsesStoredValues(): void
    {
        $tariff = (new TariffFactory())->create(
            code: 'premium',
            priceUsd: '34.90',
            priceByn: '114.00',
            priceRub: '3250.00',
        );

        $service = new TariffService(
            $this->createStub(TariffRepository::class),
            $this->createStub(EntityManagerInterface::class),
        );

        self::assertSame('34.90', $service->amountForCurrency($tariff, 'USD'));
        self::assertSame('114.00', $service->amountForCurrency($tariff, 'BYN'));
        self::assertSame('3250.00', $service->amountForCurrency($tariff, 'RUB'));
    }
}
