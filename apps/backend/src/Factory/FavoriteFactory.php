<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Favorite;
use App\Entity\Listing;
use App\Entity\User;

class FavoriteFactory
{
    public function create(User $user, Listing $listing, bool $isTest = true): Favorite
    {
        return (new Favorite())
            ->setUser($user)
            ->setListing($listing)
            ->setIsTest($isTest);
    }
}
