<?php

declare(strict_types=1);

namespace App\Dto\SiteSettings;

use App\Http\ApiErrorCode;
use Symfony\Component\Validator\Constraints as Assert;

readonly class UpdateSiteSettingsRequest
{
    public function __construct(
        #[Assert\Length(max: 5000)]
        public ?string $aboutText = null,
        #[Assert\Length(max: 64)]
        public ?string $phoneDisplay = null,
        #[Assert\Length(max: 64)]
        public ?string $phoneRaw = null,
        #[Assert\Email(message: ApiErrorCode::VALIDATION_EMAIL_INVALID)]
        #[Assert\Length(max: 255)]
        public ?string $email = null,
        #[Assert\Length(max: 255)]
        public ?string $supportHours = null,
        #[Assert\Length(max: 255)]
        public ?string $ownerName = null,
        #[Assert\Length(max: 255)]
        public ?string $address = null,
        #[Assert\Length(max: 5000)]
        public ?string $offersText = null,
        #[Assert\Length(max: 255)]
        public ?string $offersEmail = null,
        #[Assert\Length(max: 255)]
        public ?string $telegramUrl = null,
        #[Assert\Length(max: 255)]
        public ?string $whatsappUrl = null,
        #[Assert\Length(max: 255)]
        public ?string $vkUrl = null,
        public ?bool $isTest = null,
    ) {
    }
}
