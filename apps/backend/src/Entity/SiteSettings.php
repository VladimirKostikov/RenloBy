<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\SoftDeletableTrait;
use App\Entity\Trait\TestDataTrait;
use App\Repository\SiteSettingsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SiteSettingsRepository::class)]
#[ORM\Table(name: 'site_settings')]
class SiteSettings
{
    use SoftDeletableTrait;
    use TestDataTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'about_text', type: Types::TEXT)]
    private string $aboutText = '';

    #[ORM\Column(name: 'phone_display', length: 64)]
    private string $phoneDisplay = '';

    #[ORM\Column(name: 'phone_raw', length: 64)]
    private string $phoneRaw = '';

    #[ORM\Column(length: 255)]
    private string $email = '';

    #[ORM\Column(name: 'support_hours', length: 255)]
    private string $supportHours = '';

    #[ORM\Column(name: 'owner_name', length: 255, nullable: true)]
    private ?string $ownerName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(name: 'offers_text', type: Types::TEXT, nullable: true)]
    private ?string $offersText = null;

    #[ORM\Column(name: 'offers_email', length: 255, nullable: true)]
    private ?string $offersEmail = null;

    #[ORM\Column(name: 'telegram_url', length: 255, nullable: true)]
    private ?string $telegramUrl = null;

    #[ORM\Column(name: 'whatsapp_url', length: 255, nullable: true)]
    private ?string $whatsappUrl = null;

    #[ORM\Column(name: 'vk_url', length: 255, nullable: true)]
    private ?string $vkUrl = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAboutText(): string
    {
        return $this->aboutText;
    }

    public function setAboutText(string $aboutText): self
    {
        $this->aboutText = $aboutText;

        return $this;
    }

    public function getPhoneDisplay(): string
    {
        return $this->phoneDisplay;
    }

    public function setPhoneDisplay(string $phoneDisplay): self
    {
        $this->phoneDisplay = $phoneDisplay;

        return $this;
    }

    public function getPhoneRaw(): string
    {
        return $this->phoneRaw;
    }

    public function setPhoneRaw(string $phoneRaw): self
    {
        $this->phoneRaw = $phoneRaw;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getSupportHours(): string
    {
        return $this->supportHours;
    }

    public function setSupportHours(string $supportHours): self
    {
        $this->supportHours = $supportHours;

        return $this;
    }

    public function getOwnerName(): ?string
    {
        return $this->ownerName;
    }

    public function setOwnerName(?string $ownerName): self
    {
        $this->ownerName = $ownerName;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getOffersText(): ?string
    {
        return $this->offersText;
    }

    public function setOffersText(?string $offersText): self
    {
        $this->offersText = $offersText;

        return $this;
    }

    public function getOffersEmail(): ?string
    {
        return $this->offersEmail;
    }

    public function setOffersEmail(?string $offersEmail): self
    {
        $this->offersEmail = $offersEmail;

        return $this;
    }

    public function getTelegramUrl(): ?string
    {
        return $this->telegramUrl;
    }

    public function setTelegramUrl(?string $telegramUrl): self
    {
        $this->telegramUrl = $telegramUrl;

        return $this;
    }

    public function getWhatsappUrl(): ?string
    {
        return $this->whatsappUrl;
    }

    public function setWhatsappUrl(?string $whatsappUrl): self
    {
        $this->whatsappUrl = $whatsappUrl;

        return $this;
    }

    public function getVkUrl(): ?string
    {
        return $this->vkUrl;
    }

    public function setVkUrl(?string $vkUrl): self
    {
        $this->vkUrl = $vkUrl;

        return $this;
    }
}
