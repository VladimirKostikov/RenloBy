<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Collection\CollectionOwner;
use App\Entity\AiPreference;
use App\Entity\Comparison;
use App\Entity\Favorite;
use App\Entity\User;
use App\Repository\AiPreferenceRepository;
use App\Repository\ComparisonRepository;
use App\Repository\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;

class GuestCollectionMergeService
{
    public function __construct(
        private readonly FavoriteRepository $favoriteRepository,
        private readonly ComparisonRepository $comparisonRepository,
        private readonly AiPreferenceRepository $aiPreferenceRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function mergeIntoUser(User $user, string $guestSessionHash): void
    {
        $this->mergeFavorites($user, $guestSessionHash);
        $this->mergeComparisons($user, $guestSessionHash);
        $this->mergeAiPreferences($user, $guestSessionHash);
        $this->entityManager->flush();
    }

    private function mergeFavorites(User $user, string $guestSessionHash): void
    {
        foreach ($this->favoriteRepository->findByGuestSession($guestSessionHash) as $guestFavorite) {
            $listing = $guestFavorite->getListing();
            if ($listing === null) {
                $guestFavorite->softDelete();
                continue;
            }

            $existing = $this->favoriteRepository->findOneByUserAndListing($user, $listing);
            if (!$existing instanceof Favorite) {
                $favorite = (new Favorite())
                    ->setUser($user)
                    ->setListing($listing);
                $this->entityManager->persist($favorite);
            }

            $guestFavorite->softDelete();
        }
    }

    private function mergeComparisons(User $user, string $guestSessionHash): void
    {
        $userCount = count($this->comparisonRepository->findByUser($user));

        foreach ($this->comparisonRepository->findByGuestSession($guestSessionHash) as $guestComparison) {
            $listing = $guestComparison->getListing();
            if ($listing === null) {
                $guestComparison->softDelete();
                continue;
            }

            $existing = $this->comparisonRepository->findOneByUserAndListing($user, $listing);
            if ($existing instanceof Comparison) {
                $guestComparison->softDelete();
                continue;
            }

            if ($userCount >= ComparisonService::MAX_ITEMS) {
                $guestComparison->softDelete();
                continue;
            }

            $comparison = (new Comparison())
                ->setUser($user)
                ->setListing($listing);
            $this->entityManager->persist($comparison);
            ++$userCount;

            $guestComparison->softDelete();
        }
    }

    private function mergeAiPreferences(User $user, string $guestSessionHash): void
    {
        foreach ($this->aiPreferenceRepository->findByGuestSession($guestSessionHash) as $guestPreference) {
            if ($guestPreference->getUser() !== null) {
                continue;
            }

            $guestPreference
                ->setUser($user)
                ->setGuestSessionHash(null);
        }
    }
}
