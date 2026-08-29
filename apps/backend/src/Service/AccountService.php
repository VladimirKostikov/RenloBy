<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Account\AccountSummaryResponse;
use App\Dto\Auth\UpdateProfileRequest;
use App\Dto\Auth\UserResponse;
use App\Entity\User;
use App\Repository\ComparisonRepository;
use App\Repository\FavoriteRepository;
use App\Repository\ListingRepository;
use App\Repository\SavedSearchRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AccountService
{
    public function __construct(
        private readonly ListingRepository $listingRepository,
        private readonly FavoriteRepository $favoriteRepository,
        private readonly ComparisonRepository $comparisonRepository,
        private readonly SavedSearchRepository $savedSearchRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MediaUploadService $mediaUploadService,
    ) {
    }

    public function getSummary(User $user): AccountSummaryResponse
    {
        return new AccountSummaryResponse(
            $this->listingRepository->countByUser($user),
            count($this->favoriteRepository->findByUser($user)),
            count($this->comparisonRepository->findByUser($user)),
            count($this->savedSearchRepository->findBy(['user' => $user])),
        );
    }

    public function updateProfile(User $user, UpdateProfileRequest $request): UserResponse
    {
        if ($request->lastName !== null || $request->firstName !== null || $request->patronymic !== null) {
            $user->setNameParts(
                $request->lastName ?? $user->getLastName(),
                $request->firstName ?? $user->getFirstName(),
                $request->patronymic ?? $user->getPatronymic(),
            );
        }

        if ($request->phone !== null) {
            $user->setPhone($this->normalizeOptional($request->phone));
        }
        if ($request->photo !== null) {
            $user->setPhoto($this->mediaUploadService->normalizeAvatarUrl(
                $request->photo === '' ? null : $request->photo
            ));
        }
        if ($request->instagram !== null) {
            $user->setInstagram($this->normalizeOptional($request->instagram));
        }
        if ($request->telegram !== null) {
            $user->setTelegram($this->normalizeOptional($request->telegram));
        }
        if ($request->whatsapp !== null) {
            $user->setWhatsapp($this->normalizeOptional($request->whatsapp));
        }
        if ($request->viber !== null) {
            $user->setViber($this->normalizeOptional($request->viber));
        }

        $this->entityManager->flush();

        return UserResponse::fromEntity($user);
    }

    public function uploadPhoto(User $user, UploadedFile $file): UserResponse
    {
        $uploaded = $this->mediaUploadService->uploadAvatar($file, $user);
        $user->setPhoto($uploaded->url);
        $this->entityManager->flush();

        return UserResponse::fromEntity($user);
    }

    private function normalizeOptional(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
