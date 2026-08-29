<?php

declare(strict_types=1);

namespace App\Dto\SeoMeta;

use App\Entity\SeoMeta;

readonly class SeoMetaResponse
{
    public function __construct(
        public int $id,
        public string $pageKey,
        public string $locale,
        public string $title,
        public string $description,
        public ?string $h1,
        public ?string $keywords,
        public bool $isTest,
    ) {
    }

    public static function fromEntity(SeoMeta $meta): self
    {
        return new self(
            $meta->getId() ?? 0,
            $meta->getPageKey(),
            $meta->getLocale(),
            $meta->getTitle(),
            $meta->getDescription(),
            $meta->getH1(),
            $meta->getKeywords(),
            $meta->isTest(),
        );
    }
}
