<?php

declare(strict_types=1);

namespace App\Dto\AiAssistant;

use App\Dto\Listing\ListingResponse;
use App\Entity\AiPreference;

readonly class AiPreferenceResponse
{
    /**
     * @param array<string, mixed> $answers
     * @param array<string, mixed> $filters
     * @param list<int> $recommendedListingIds
     * @param list<string> $highlights
     * @param list<ListingResponse> $listings
     */
    public function __construct(
        public int $id,
        public ?int $userId,
        public ?string $guestSessionHash,
        public array $answers,
        public array $filters,
        public array $recommendedListingIds,
        public ?string $summary,
        public array $highlights,
        public array $listings,
        public bool $isTest,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    /**
     * @param list<ListingResponse> $listings
     */
    public static function fromEntity(AiPreference $preference, array $listings = []): self
    {
        return new self(
            $preference->getId() ?? 0,
            $preference->getUser()?->getId(),
            $preference->getGuestSessionHash(),
            $preference->getAnswers(),
            $preference->getFilters(),
            $preference->getRecommendedListingIds(),
            $preference->getSummary(),
            $preference->getHighlights(),
            $listings,
            $preference->isTest(),
            $preference->getCreatedAt()->format(\DateTimeInterface::ATOM),
            $preference->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
