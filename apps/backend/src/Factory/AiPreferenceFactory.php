<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\AiPreference;
use App\Entity\User;

class AiPreferenceFactory
{
    /**
     * @param array<string, mixed> $answers
     * @param array<string, mixed> $filters
     * @param list<int> $recommendedListingIds
     * @param list<string> $highlights
     */
    public function create(
        ?User $user = null,
        ?string $guestSessionHash = null,
        array $answers = [],
        array $filters = [],
        array $recommendedListingIds = [],
        ?string $summary = null,
        array $highlights = [],
        bool $isTest = true,
    ): AiPreference {
        $preference = (new AiPreference())
            ->setUser($user)
            ->setGuestSessionHash($guestSessionHash)
            ->setAnswers($answers)
            ->setFilters($filters)
            ->setRecommendedListingIds($recommendedListingIds)
            ->setSummary($summary)
            ->setHighlights($highlights)
            ->setIsTest($isTest);

        return $preference;
    }
}
