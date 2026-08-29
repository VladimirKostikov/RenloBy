<?php

declare(strict_types=1);

namespace App\Dto\SiteSettings;

use App\Entity\SiteSettings;

readonly class SiteSettingsResponse
{
    public function __construct(
        public int $id,
        public string $aboutText,
        public string $phoneDisplay,
        public string $phoneRaw,
        public string $email,
        public string $supportHours,
        public ?string $ownerName,
        public ?string $address,
        public ?string $offersText,
        public ?string $offersEmail,
        public ?string $telegramUrl,
        public ?string $whatsappUrl,
        public ?string $vkUrl,
        public bool $isTest,
    ) {
    }

    public static function fromEntity(SiteSettings $settings): self
    {
        return new self(
            $settings->getId() ?? 0,
            $settings->getAboutText(),
            $settings->getPhoneDisplay(),
            $settings->getPhoneRaw(),
            $settings->getEmail(),
            $settings->getSupportHours(),
            $settings->getOwnerName(),
            $settings->getAddress(),
            $settings->getOffersText(),
            $settings->getOffersEmail(),
            $settings->getTelegramUrl(),
            $settings->getWhatsappUrl(),
            $settings->getVkUrl(),
            $settings->isTest(),
        );
    }
}
