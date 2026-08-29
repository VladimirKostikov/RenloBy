<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\SiteSettings;

class SiteSettingsFactory
{
    public function create(
        string $aboutText,
        string $phoneDisplay,
        string $phoneRaw,
        string $email,
        string $supportHours,
        ?string $ownerName = null,
        ?string $address = null,
        ?string $offersText = null,
        ?string $offersEmail = null,
        ?string $telegramUrl = null,
        ?string $whatsappUrl = null,
        ?string $vkUrl = null,
        bool $isTest = true,
    ): SiteSettings {
        return (new SiteSettings())
            ->setAboutText($aboutText)
            ->setPhoneDisplay($phoneDisplay)
            ->setPhoneRaw($phoneRaw)
            ->setEmail($email)
            ->setSupportHours($supportHours)
            ->setOwnerName($ownerName)
            ->setAddress($address)
            ->setOffersText($offersText)
            ->setOffersEmail($offersEmail)
            ->setTelegramUrl($telegramUrl)
            ->setWhatsappUrl($whatsappUrl)
            ->setVkUrl($vkUrl)
            ->setIsTest($isTest);
    }
}
