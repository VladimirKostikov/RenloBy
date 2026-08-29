<?php

declare(strict_types=1);

namespace App\Dto\Tariff;

use App\Http\ApiErrorCode;
use Symfony\Component\Validator\Constraints as Assert;

readonly class UpdateTariffRequest
{
    public function __construct(
        #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: ApiErrorCode::VALIDATION_FAILED)]
        public ?string $priceUsd = null,
        #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: ApiErrorCode::VALIDATION_FAILED)]
        public ?string $priceByn = null,
        #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: ApiErrorCode::VALIDATION_FAILED)]
        public ?string $priceRub = null,
        #[Assert\Length(exactly: 3)]
        public ?string $currency = null,
        public ?bool $isPopular = null,
        public ?int $sortOrder = null,
        public ?bool $isTest = null,
    ) {
    }
}
