<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Listing\ListingGoodPriceVerdict;
use App\Entity\Listing;
use App\Enum\DealType;
use App\Enum\RentTerm;
use App\Repository\ListingRepository;

/**
 * Market-based "AI good price" for Belarus listings (prices stored in USD).
 * Compares a listing to published peers and applies BY market heuristics:
 * agency commission savings, owner offers, renovation, metro liquidity in large cities.
 */
class ListingGoodPriceEvaluator
{
    private const MIN_PEERS_STRICT = 5;
    private const MIN_PEERS_RELAXED = 3;
    private const GOOD_DELTA_PCT = -7.0;
    private const BORDERLINE_DELTA_PCT = -3.0;
    private const GOOD_SCORE = 70;
    private const AREA_TOLERANCE_PCT = 0.2;

    public function __construct(
        private readonly ListingRepository $listingRepository,
        private readonly CurrencyConverter $currencyConverter,
    ) {
    }

    public function evaluate(Listing $listing): ListingGoodPriceVerdict
    {
        $metric = $this->metricKey($listing);
        $listingValue = $this->metricValue($listing);
        if ($listingValue <= 0) {
            return new ListingGoodPriceVerdict(false, 0, 0.0, 0, 0, 0, $metric, ['invalid_price']);
        }

        $peers = $this->collectPeers($listing);
        $peerValues = [];
        foreach ($peers as $peer) {
            $value = $this->metricValue($peer);
            if ($value > 0) {
                $peerValues[] = $value;
            }
        }

        $peerCount = count($peerValues);
        $minPeers = $this->isThickMarket($listing) ? self::MIN_PEERS_STRICT : self::MIN_PEERS_RELAXED;
        if ($peerCount < $minPeers) {
            return new ListingGoodPriceVerdict(
                false,
                20,
                0.0,
                $peerCount,
                $listingValue,
                $listingValue,
                $metric,
                ['insufficient_peers'],
            );
        }

        sort($peerValues);
        $median = $this->percentile($peerValues, 0.5);
        $p40 = $this->percentile($peerValues, 0.4);
        $deltaPct = $median > 0
            ? round((($listingValue - $median) / $median) * 100, 1)
            : 0.0;

        $score = 50;
        $signals = [];

        if ($listingValue <= $p40) {
            $score += 28;
            $signals[] = 'at_or_below_p40';
        }
        if ($deltaPct <= self::GOOD_DELTA_PCT) {
            $score += 22;
            $signals[] = 'below_median_7pct';
        } elseif ($deltaPct <= self::BORDERLINE_DELTA_PCT) {
            $score += 10;
            $signals[] = 'below_median_3pct';
        } elseif ($deltaPct > 2.0) {
            $score -= 25;
            $signals[] = 'above_median';
        }

        if ($listing->isFromOwner()) {
            $score += 6;
            $signals[] = 'from_owner';
        }
        if ($listing->isNoCommission()) {
            $score += 5;
            $signals[] = 'no_commission';
        }
        if ($listing->hasRenovation() && $listing->getDealType() !== DealType::Rent) {
            $score += 4;
            $signals[] = 'renovation';
        }
        if ($this->hasMetroLiquidityBonus($listing)) {
            $score += 4;
            $signals[] = 'metro_liquidity';
        }

        // Soft context: BYN affordability band from configured FX (listings are USD-quoted).
        $byn = $this->currencyConverter->usdToBynAmount($listing->getPrice());
        if ($byn > 0) {
            $signals[] = 'fx_usd_byn_context';
        }

        $score = max(0, min(100, $score));
        $isGood = $score >= self::GOOD_SCORE
            && $deltaPct <= self::BORDERLINE_DELTA_PCT
            && !in_array('above_median', $signals, true);

        return new ListingGoodPriceVerdict(
            $isGood,
            $score,
            $deltaPct,
            $peerCount,
            (int) round($median),
            $listingValue,
            $metric,
            $signals,
        );
    }

    public function apply(Listing $listing): ListingGoodPriceVerdict
    {
        $verdict = $this->evaluate($listing);
        $listing->setAiGoodPrice($verdict->isGoodPrice);

        return $verdict;
    }

    /**
     * @return list<Listing>
     */
    private function collectPeers(Listing $listing): array
    {
        $candidates = $this->listingRepository->findMarketComps($listing, 80);
        $area = $listing->getArea();
        $filtered = [];

        foreach ($candidates as $peer) {
            if ($peer->isTest() !== $listing->isTest()) {
                continue;
            }
            if ($listing->getDealType() === DealType::Rent
                && $listing->getRentTerm() instanceof RentTerm
                && $peer->getRentTerm() !== null
                && $peer->getRentTerm() !== $listing->getRentTerm()
            ) {
                continue;
            }
            if ($area > 0) {
                $peerArea = $peer->getArea();
                if ($peerArea <= 0) {
                    continue;
                }
                $ratio = abs($peerArea - $area) / $area;
                if ($ratio > self::AREA_TOLERANCE_PCT) {
                    continue;
                }
            }
            $filtered[] = $peer;
        }

        if (count($filtered) >= self::MIN_PEERS_RELAXED) {
            return $filtered;
        }

        return array_values(array_filter(
            $candidates,
            fn (Listing $peer) => $peer->isTest() === $listing->isTest(),
        ));
    }

    private function metricKey(Listing $listing): string
    {
        return $listing->getDealType() === DealType::Rent ? 'monthly_price_usd' : 'price_per_sqm_usd';
    }

    private function metricValue(Listing $listing): int
    {
        if ($listing->getDealType() === DealType::Rent) {
            return max(0, $listing->getPrice());
        }

        return max(0, $listing->getPricePerSqm());
    }

    private function isThickMarket(Listing $listing): bool
    {
        $slug = $listing->getCity()?->getSlug() ?? '';

        return in_array($slug, ['minsk', 'brest-city', 'gomel-city', 'grodno-city', 'vitebsk-city', 'mogilev-city'], true);
    }

    private function hasMetroLiquidityBonus(Listing $listing): bool
    {
        if ($listing->getCity()?->getSlug() !== 'minsk') {
            return false;
        }
        $minutes = $listing->getMetroMinutes();

        return $minutes !== null && $minutes <= 15;
    }

    /**
     * @param list<int> $sorted
     */
    private function percentile(array $sorted, float $p): float
    {
        $n = count($sorted);
        if ($n === 0) {
            return 0.0;
        }
        if ($n === 1) {
            return (float) $sorted[0];
        }

        $index = ($n - 1) * $p;
        $low = (int) floor($index);
        $high = (int) ceil($index);
        if ($low === $high) {
            return (float) $sorted[$low];
        }

        $weight = $index - $low;

        return $sorted[$low] * (1 - $weight) + $sorted[$high] * $weight;
    }
}
