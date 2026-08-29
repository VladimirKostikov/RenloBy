<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Listing\ListingMarketSnapshot;
use App\Entity\Listing;
use App\Enum\DealType;
use App\Exception\ResourceNotFoundException;
use App\Http\ApiErrorCode;
use App\Repository\ListingRepository;

final class ListingMarketService
{
    public function __construct(
        private readonly ListingRepository $listingRepository,
    ) {
    }

    public function snapshotForId(int $id): ListingMarketSnapshot
    {
        $listing = $this->listingRepository->find($id);
        if ($listing === null) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_LISTING);
        }

        return $this->snapshot($listing);
    }

    public function snapshot(Listing $listing): ListingMarketSnapshot
    {
        $similar = $this->listingRepository->findSimilarNearby($listing, 42);
        $usePerSqm = $listing->getDealType() !== DealType::Rent;
        $current = $usePerSqm ? max(0, $listing->getPricePerSqm()) : max(0, $listing->getPrice());

        $values = [];
        foreach ($similar as $item) {
            $value = $usePerSqm ? $item->getPricePerSqm() : $item->getPrice();
            if ($value > 0) {
                $values[] = $value;
            }
        }

        if ($current > 0) {
            $values[] = $current;
        }

        sort($values);
        $avg = $values !== [] ? (int) round(array_sum($values) / count($values)) : $current;
        $min = $values !== [] ? $values[0] : $current;
        $max = $values !== [] ? $values[array_key_last($values)] : $current;

        $ranked = $similar;
        $ranked[] = $listing;
        usort(
            $ranked,
            static function (Listing $a, Listing $b) use ($usePerSqm): int {
                $left = $usePerSqm ? $a->getPricePerSqm() : $a->getPrice();
                $right = $usePerSqm ? $b->getPricePerSqm() : $b->getPrice();

                return $left <=> $right;
            },
        );

        $rank = 1;
        foreach ($ranked as $index => $item) {
            if ($item->getId() === $listing->getId()) {
                $rank = $index + 1;
                break;
            }
        }

        $changePct = $avg > 0
            ? round((($current - $avg) / $avg) * 100, 1)
            : 0.0;

        return new ListingMarketSnapshot(
            $usePerSqm ? 'price_per_sqm' : 'price',
            $current,
            $avg,
            $min,
            $max,
            $rank,
            max(count($similar), $current > 0 ? 1 : 0),
            $changePct,
            $listing->isAiGoodPrice(),
        );
    }
}
