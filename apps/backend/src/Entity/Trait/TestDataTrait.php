<?php

declare(strict_types=1);

namespace App\Entity\Trait;

use Doctrine\ORM\Mapping as ORM;

trait TestDataTrait
{
    #[ORM\Column(name: 'is_test', options: ['default' => false])]
    private bool $isTest = false;

    public function isTest(): bool
    {
        return $this->isTest;
    }

    public function setIsTest(bool $isTest): self
    {
        $this->isTest = $isTest;

        return $this;
    }
}
