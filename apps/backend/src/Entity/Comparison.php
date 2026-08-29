<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\SoftDeletableTrait;
use App\Entity\Trait\TestDataTrait;
use App\Repository\ComparisonRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ComparisonRepository::class)]
#[ORM\Table(name: 'comparisons')]
#[ORM\UniqueConstraint(name: 'comparisons_user_listing_unique', columns: ['user_id', 'listing_id'])]
#[ORM\UniqueConstraint(name: 'comparisons_guest_listing_unique', columns: ['guest_session_hash', 'listing_id'])]
class Comparison
{
    use SoftDeletableTrait;
    use TestDataTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'comparisons')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $guestSessionHash = null;

    #[ORM\ManyToOne(inversedBy: 'comparisons')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Listing $listing = null;

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

    public function getListing(): ?Listing
    {
        return $this->listing;
    }

    public function setListing(?Listing $listing): self
    {
        $this->listing = $listing;

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
}
