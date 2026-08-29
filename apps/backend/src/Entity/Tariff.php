<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\SoftDeletableTrait;
use App\Entity\Trait\TestDataTrait;
use App\Repository\TariffRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TariffRepository::class)]
#[ORM\Table(name: 'tariffs')]
#[ORM\UniqueConstraint(name: 'uniq_tariffs_code_is_test', columns: ['code', 'is_test'])]
class Tariff
{
    use SoftDeletableTrait;
    use TestDataTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32)]
    private string $code = '';

    #[ORM\Column(name: 'price_usd', type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $priceUsd = '0.00';

    #[ORM\Column(name: 'price_byn', type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $priceByn = '0.00';

    #[ORM\Column(name: 'price_rub', type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $priceRub = '0.00';

    #[ORM\Column(length: 3)]
    private string $currency = 'USD';

    #[ORM\Column]
    private bool $isPopular = false;

    #[ORM\Column]
    private int $sortOrder = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getPriceUsd(): string
    {
        return $this->priceUsd;
    }

    public function setPriceUsd(string $priceUsd): self
    {
        $this->priceUsd = $priceUsd;

        return $this;
    }

    public function getPriceByn(): string
    {
        return $this->priceByn;
    }

    public function setPriceByn(string $priceByn): self
    {
        $this->priceByn = $priceByn;

        return $this;
    }

    public function getPriceRub(): string
    {
        return $this->priceRub;
    }

    public function setPriceRub(string $priceRub): self
    {
        $this->priceRub = $priceRub;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function isPopular(): bool
    {
        return $this->isPopular;
    }

    public function setIsPopular(bool $isPopular): self
    {
        $this->isPopular = $isPopular;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }
}
