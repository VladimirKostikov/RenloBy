<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\SoftDeletableTrait;
use App\Entity\Trait\TestDataTrait;
use App\Enum\InfoPageCategory;
use App\Repository\InfoPageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InfoPageRepository::class)]
#[ORM\Table(name: 'info_pages')]
#[ORM\UniqueConstraint(name: 'uniq_info_pages_slug_is_test', columns: ['slug', 'is_test'])]
class InfoPage
{
    use SoftDeletableTrait;
    use TestDataTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $slug = '';

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $body = '';

    #[ORM\Column(enumType: InfoPageCategory::class, length: 30)]
    private InfoPageCategory $category = InfoPageCategory::DealSafety;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $importantNote = null;

    #[ORM\Column(type: Types::JSON)]
    private array $faqItems = [];

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $metaTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $metaDescription = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

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

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getCategory(): InfoPageCategory
    {
        return $this->category;
    }

    public function setCategory(InfoPageCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getImportantNote(): ?string
    {
        return $this->importantNote;
    }

    public function setImportantNote(?string $importantNote): self
    {
        $this->importantNote = $importantNote;

        return $this;
    }

    public function getFaqItems(): array
    {
        return $this->faqItems;
    }

    public function setFaqItems(array $faqItems): self
    {
        $this->faqItems = $faqItems;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): self
    {
        $this->metaTitle = $metaTitle;

        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): self
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function touch(): self
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
}
