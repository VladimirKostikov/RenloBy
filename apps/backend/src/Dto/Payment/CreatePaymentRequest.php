<?php

declare(strict_types=1);

namespace App\Dto\Payment;

use App\Http\ApiErrorCode;
use Symfony\Component\Validator\Constraints as Assert;

readonly class CreatePaymentRequest
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: ApiErrorCode::VALIDATION_FAILED)]
        public string $amount = '0',
        #[Assert\Choice(choices: ['USD', 'BYN', 'RUB'], message: ApiErrorCode::VALIDATION_FAILED)]
        public string $currency = 'BYN',
        #[Assert\Length(max: 250)]
        public string $description = '',
        #[Assert\NotBlank(message: ApiErrorCode::VALIDATION_FAILED)]
        #[Assert\Url(message: ApiErrorCode::VALIDATION_FAILED)]
        public string $returnUrl = '',
        public array $metadata = [],
        public bool $isTest = false,
    ) {
    }
}
