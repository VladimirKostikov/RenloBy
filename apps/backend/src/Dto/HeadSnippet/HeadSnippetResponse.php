<?php

declare(strict_types=1);

namespace App\Dto\HeadSnippet;

use App\Entity\HeadSnippet;

readonly class HeadSnippetResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public string $code,
        public bool $isEnabled,
        public int $sortOrder,
        public bool $isTest,
    ) {
    }

    public static function fromEntity(HeadSnippet $snippet): self
    {
        return new self(
            $snippet->getId() ?? 0,
            $snippet->getName(),
            $snippet->getCode(),
            $snippet->isEnabled(),
            $snippet->getSortOrder(),
            $snippet->isTest(),
        );
    }
}
