<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\SavedSearch;
use App\Entity\User;

class SavedSearchFactory
{
    public function create(User $user, string $name = 'Search', array $filters = [], bool $isTest = true): SavedSearch
    {
        return (new SavedSearch())
            ->setUser($user)
            ->setName($name)
            ->setFilters($filters)
            ->setIsTest($isTest);
    }
}
