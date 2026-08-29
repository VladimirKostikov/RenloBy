<?php

declare(strict_types=1);

namespace App\Payment\YooKassa;

readonly class YooKassaPaymentResult
{
    public function __construct(
        public string $providerPaymentId,
        public string $status,
        public ?string $confirmationUrl,
        public array $raw = [],
    ) {
    }
}
