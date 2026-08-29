<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\SoftDeletableTrait;
use App\Entity\Trait\TestDataTrait;
use App\Repository\SeoMetaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SeoMetaRepository::class)]
#[ORM\Table(name: 'seo_meta')]
#[ORM\UniqueConstraint(name: 'uniq_seo_meta_page_locale_is_test', columns: ['page_key', 'locale', 'is_test'])]
class SeoMeta
{
    use SoftDeletableTrait;
    use TestDataTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'page_key', length: 64)]
    private string $pageKey = '';

    #[ORM\Column(length: 5)]
    private string $locale = 'ru';

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $description = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $h1 = null;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $keywords = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPageKey(): string
    {
        return $this->pageKey;
    }

    public function setPageKey(string $pageKey): self
    {
        $this->pageKey = $pageKey;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getH1(): ?string
    {
        return $this->h1;
    }

    public function setH1(?string $h1): self
    {
        $this->h1 = $h1;

        return $this;
    }

    public function getKeywords(): ?string
    {
        return $this->keywords;
    }

    public function setKeywords(?string $keywords): self
    {
        $this->keywords = $keywords;

        return $this;
    }
}
