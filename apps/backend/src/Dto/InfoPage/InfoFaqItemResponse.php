<?php

declare(strict_types=1);

namespace App\Dto\InfoPage;

readonly class InfoFaqItemResponse
{
    public function __construct(
        public string $question,
        public string $answer,
    ) {
    }
}
