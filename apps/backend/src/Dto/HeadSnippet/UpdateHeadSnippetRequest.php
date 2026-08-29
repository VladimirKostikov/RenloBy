<?php

declare(strict_types=1);

namespace App\Dto\HeadSnippet;

use Symfony\Component\Validator\Constraints as Assert;

readonly class UpdateHeadSnippetRequest
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public ?string $name = null,
        #[Assert\Length(max: 100000)]
        public ?string $code = null,
        public ?bool $isEnabled = null,
        public ?int $sortOrder = null,
        public ?bool $isTest = null,
    ) {
    }
}
