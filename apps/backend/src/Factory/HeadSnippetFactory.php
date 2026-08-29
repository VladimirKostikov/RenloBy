<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\HeadSnippet;

class HeadSnippetFactory
{
    public function create(
        string $name,
        string $code,
        bool $isEnabled = true,
        int $sortOrder = 0,
        bool $isTest = true,
    ): HeadSnippet {
        return (new HeadSnippet())
            ->setName($name)
            ->setCode($code)
            ->setIsEnabled($isEnabled)
            ->setSortOrder($sortOrder)
            ->setIsTest($isTest);
    }
}
