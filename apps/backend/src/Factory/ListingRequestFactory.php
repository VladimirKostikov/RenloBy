<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Listing;
use App\Entity\ListingRequest;
use App\Entity\User;
use App\Enum\ListingRequestStatus;

class ListingRequestFactory
{
    public function create(
        Listing $listing,
        string $phone = '+375291112233',
        string $message = 'Интересует квартира, прошу перезвонить.',
        ?string $name = null,
        ?User $requester = null,
        ListingRequestStatus $status = ListingRequestStatus::New,
        bool $isTest = true,
    ): ListingRequest {
        return (new ListingRequest())
            ->setListing($listing)
            ->setRequester($requester)
            ->setName($name)
            ->setPhone($phone)
            ->setMessage($message)
            ->setStatus($status)
            ->setIsTest($isTest);
    }
}
