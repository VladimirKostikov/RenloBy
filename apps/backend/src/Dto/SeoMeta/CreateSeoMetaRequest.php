<?php

declare(strict_types=1);

namespace App\Dto\SeoMeta;

use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateSeoMetaRequest
{
    public function __construct(
        public string $pageKey,
        public string $locale,
        public string $title,
        public string $description,
        public ?string $h1,
        #[Assert\Length(max: 512)]
        public ?string $keywords = null,
        public ?bool $isTest = null,
    ) {
    }
}
