<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Tariff\TariffResponse;
use App\Dto\Tariff\UpdateTariffRequest;
use App\Entity\Tariff;
use App\Exception\ResourceNotFoundException;
use App\Exception\ValidationException;
use App\Http\ApiErrorCode;
use App\Repository\TariffRepository;
use Doctrine\ORM\EntityManagerInterface;

class TariffService
{
    public function __construct(
        private readonly TariffRepository $tariffRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return list<TariffResponse>
     */
    public function list(): array
    {
        return array_map(
            static fn (Tariff $tariff) => TariffResponse::fromEntity($tariff),
            $this->tariffRepository->findAllOrdered()
        );
    }

    public function get(int $id): TariffResponse
    {
        return TariffResponse::fromEntity($this->findEntity($id));
    }

    public function update(int $id, UpdateTariffRequest $request): TariffResponse
    {
        $tariff = $this->findEntity($id);

        if ($request->priceUsd !== null) {
            $tariff->setPriceUsd($this->normalizePrice($request->priceUsd, 'priceUsd'));
        }
        if ($request->priceByn !== null) {
            $tariff->setPriceByn($this->normalizePrice($request->priceByn, 'priceByn'));
        }
        if ($request->priceRub !== null) {
            $tariff->setPriceRub($this->normalizePrice($request->priceRub, 'priceRub'));
        }
        if ($request->currency !== null) {
            $tariff->setCurrency(strtoupper(trim($request->currency)));
        }
        if ($request->isPopular !== null) {
            $tariff->setIsPopular($request->isPopular);
        }
        if ($request->sortOrder !== null) {
            $tariff->setSortOrder($request->sortOrder);
        }
        if ($request->isTest !== null) {
            $tariff->setIsTest($request->isTest);
        }

        $this->entityManager->flush();

        return TariffResponse::fromEntity($tariff);
    }

    public function delete(int $id): void
    {
        $tariff = $this->findEntity($id);
        $tariff->softDelete();
        $this->entityManager->flush();
    }

    public function findActiveByCode(string $code, bool $isTest = false): Tariff
    {
        $tariff = $this->tariffRepository->findOneByCode($code, $isTest);
        if (!$tariff instanceof Tariff) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_TARIFF);
        }

        return $tariff;
    }

    public function amountForCurrency(Tariff $tariff, string $currency): string
    {
        return match (strtoupper(trim($currency))) {
            'BYN' => $tariff->getPriceByn(),
            'RUB' => $tariff->getPriceRub(),
            default => $tariff->getPriceUsd(),
        };
    }

    public function findEntity(int $id): Tariff
    {
        $tariff = $this->tariffRepository->find($id);
        if (!$tariff instanceof Tariff) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_TARIFF);
        }

        return $tariff;
    }

    private function normalizePrice(string $price, string $field): string
    {
        $normalized = number_format((float) str_replace(',', '.', $price), 2, '.', '');
        if ((float) $normalized <= 0) {
            throw new ValidationException([$field => ApiErrorCode::VALIDATION_FAILED]);
        }

        return $normalized;
    }
}
