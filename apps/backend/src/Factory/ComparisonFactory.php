<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Comparison;
use App\Entity\Listing;
use App\Entity\User;

class ComparisonFactory
{
    public function create(User $user, Listing $listing, bool $isTest = true): Comparison
    {
        return (new Comparison())
            ->setUser($user)
            ->setListing($listing)
            ->setIsTest($isTest);
    }
}
