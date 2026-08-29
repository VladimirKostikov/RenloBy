<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\City;
use App\Entity\District;
use App\Entity\Listing;
use App\Entity\MetroStation;
use App\Entity\User;
use App\Enum\DealType;
use App\Enum\ListingStatus;
use App\Enum\ListingType;

class ListingFactory
{
    public function create(
        User $user,
        City $city,
        District $district,
        DealType $dealType = DealType::Sale,
        ListingType $listingType = ListingType::Apartment,
        ?MetroStation $metroStation = null,
        bool $isTest = true,
    ): Listing {
        $listing = (new Listing())
            ->setUser($user)
            ->setCity($city)
            ->setDistrict($district)
            ->setDealType($dealType)
            ->setListingType($listingType)
            ->setStatus(ListingStatus::Published)
            ->setPrice(100000)
            ->setPricePerSqm(2000)
            ->setRooms(2)
            ->setArea(50.0)
            ->setFloor(3)
            ->setTotalFloors(9)
            ->setAddress('Minsk')
            ->setLatitude(53.9)
            ->setLongitude(27.5667)
            ->setVerified(false)
            ->setAiGoodPrice(false)
            ->setViews(0)
            ->setImages([]);

        if ($metroStation !== null) {
            $listing->setMetroStation($metroStation);
            $listing->setMetroMinutes(10);
        }

        return $listing->setIsTest($isTest);
    }
}
