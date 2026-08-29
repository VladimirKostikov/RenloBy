<?php

declare(strict_types=1);

namespace App\Dto\HeadSnippet;

readonly class PublicHeadSnippetResponse
{
    public function __construct(
        public string $code,
    ) {
    }
}
