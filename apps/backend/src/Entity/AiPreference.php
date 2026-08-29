<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\SoftDeletableTrait;
use App\Entity\Trait\TestDataTrait;
use App\Repository\AiPreferenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AiPreferenceRepository::class)]
#[ORM\Table(name: 'ai_preferences')]
#[ORM\Index(name: 'idx_ai_preferences_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_ai_preferences_guest', columns: ['guest_session_hash'])]
class AiPreference
{
    use SoftDeletableTrait;
    use TestDataTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $guestSessionHash = null;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $answers = [];

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $filters = [];

    /** @var list<int> */
    #[ORM\Column(type: Types::JSON)]
    private array $recommendedListingIds = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $summary = null;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $highlights = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getGuestSessionHash(): ?string
    {
        return $this->guestSessionHash;
    }

    public function setGuestSessionHash(?string $guestSessionHash): self
    {
        $this->guestSessionHash = $guestSessionHash;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAnswers(): array
    {
        return $this->answers;
    }

    /**
     * @param array<string, mixed> $answers
     */
    public function setAnswers(array $answers): self
    {
        $this->answers = $answers;
        $this->touch();

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function setFilters(array $filters): self
    {
        $this->filters = $filters;
        $this->touch();

        return $this;
    }

    /**
     * @return list<int>
     */
    public function getRecommendedListingIds(): array
    {
        return $this->recommendedListingIds;
    }

    /**
     * @param list<int> $recommendedListingIds
     */
    public function setRecommendedListingIds(array $recommendedListingIds): self
    {
        $this->recommendedListingIds = array_values(array_map('intval', $recommendedListingIds));
        $this->touch();

        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): self
    {
        $this->summary = $summary;
        $this->touch();

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getHighlights(): array
    {
        return $this->highlights;
    }

    /**
     * @param list<string> $highlights
     */
    public function setHighlights(array $highlights): self
    {
        $this->highlights = array_values($highlights);
        $this->touch();

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
