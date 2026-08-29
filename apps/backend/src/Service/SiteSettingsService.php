<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\SiteSettings\SiteSettingsResponse;
use App\Dto\SiteSettings\UpdateSiteSettingsRequest;
use App\Entity\SiteSettings;
use App\Exception\ResourceNotFoundException;
use App\Http\ApiErrorCode;
use App\Repository\SiteSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;

class SiteSettingsService
{
    public function __construct(
        private readonly SiteSettingsRepository $siteSettingsRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return list<SiteSettingsResponse>
     */
    public function list(): array
    {
        return array_map(
            static fn (SiteSettings $settings) => SiteSettingsResponse::fromEntity($settings),
            $this->siteSettingsRepository->findBy([], ['id' => 'ASC'])
        );
    }

    public function getCurrent(): SiteSettingsResponse
    {
        return SiteSettingsResponse::fromEntity($this->findCurrentEntity());
    }

    public function get(int $id): SiteSettingsResponse
    {
        return SiteSettingsResponse::fromEntity($this->findEntity($id));
    }

    public function update(int $id, UpdateSiteSettingsRequest $request): SiteSettingsResponse
    {
        $settings = $this->findEntity($id);
        $this->applyUpdate($settings, $request);
        $this->entityManager->flush();

        return SiteSettingsResponse::fromEntity($settings);
    }

    public function delete(int $id): void
    {
        $settings = $this->findEntity($id);
        $settings->softDelete();
        $this->entityManager->flush();
    }

    public function findCurrentEntity(): SiteSettings
    {
        $settings = $this->siteSettingsRepository->findCurrent();
        if (!$settings instanceof SiteSettings) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_SITE_SETTINGS);
        }

        return $settings;
    }

    public function findEntity(int $id): SiteSettings
    {
        $settings = $this->siteSettingsRepository->find($id);
        if (!$settings instanceof SiteSettings) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_SITE_SETTINGS);
        }

        return $settings;
    }

    private function applyUpdate(SiteSettings $settings, UpdateSiteSettingsRequest $request): void
    {
        if ($request->aboutText !== null) {
            $settings->setAboutText(trim($request->aboutText));
        }
        if ($request->phoneDisplay !== null) {
            $settings->setPhoneDisplay(trim($request->phoneDisplay));
        }
        if ($request->phoneRaw !== null) {
            $settings->setPhoneRaw(preg_replace('/\s+/', '', trim($request->phoneRaw)) ?? trim($request->phoneRaw));
        }
        if ($request->email !== null) {
            $settings->setEmail(trim($request->email));
        }
        if ($request->supportHours !== null) {
            $settings->setSupportHours(trim($request->supportHours));
        }
        if ($request->ownerName !== null) {
            $value = trim($request->ownerName);
            $settings->setOwnerName($value === '' ? null : $value);
        }
        if ($request->address !== null) {
            $value = trim($request->address);
            $settings->setAddress($value === '' ? null : $value);
        }
        if ($request->offersText !== null) {
            $value = trim($request->offersText);
            $settings->setOffersText($value === '' ? null : $value);
        }
        if ($request->offersEmail !== null) {
            $value = trim($request->offersEmail);
            if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new \App\Exception\ValidationException(['offersEmail' => ApiErrorCode::VALIDATION_EMAIL_INVALID]);
            }
            $settings->setOffersEmail($value === '' ? null : $value);
        }
        if ($request->telegramUrl !== null) {
            $settings->setTelegramUrl($this->normalizeOptionalHttpUrl($request->telegramUrl, 'telegramUrl'));
        }
        if ($request->whatsappUrl !== null) {
            $settings->setWhatsappUrl($this->normalizeOptionalHttpUrl($request->whatsappUrl, 'whatsappUrl'));
        }
        if ($request->vkUrl !== null) {
            $settings->setVkUrl($this->normalizeOptionalHttpUrl($request->vkUrl, 'vkUrl'));
        }
        if ($request->isTest !== null) {
            $settings->setIsTest($request->isTest);
        }
    }

    private function normalizeOptionalHttpUrl(string $raw, string $field): ?string
    {
        $value = trim($raw);
        if ($value === '') {
            return null;
        }
        if (!preg_match('#^https?://#i', $value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new \App\Exception\ValidationException([$field => ApiErrorCode::VALIDATION_SOCIAL_INVALID]);
        }

        return $value;
    }
}
