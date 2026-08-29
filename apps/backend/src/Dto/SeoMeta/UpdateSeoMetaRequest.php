<?php

declare(strict_types=1);

namespace App\Dto\SeoMeta;

use Symfony\Component\Validator\Constraints as Assert;

readonly class UpdateSeoMetaRequest
{
    public function __construct(
        public ?string $pageKey = null,
        public ?string $locale = null,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $h1 = null,
        #[Assert\Length(max: 512)]
        public ?string $keywords = null,
        public ?bool $isTest = null,
    ) {
    }
}
