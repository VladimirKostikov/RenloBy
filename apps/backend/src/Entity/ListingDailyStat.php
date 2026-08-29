<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ListingDailyStatRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ListingDailyStatRepository::class)]
#[ORM\Table(name: 'listing_daily_stats')]
#[ORM\UniqueConstraint(name: 'uniq_listing_daily_stats_listing_day', columns: ['listing_id', 'day'])]
class ListingDailyStat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Listing $listing = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $day;

    #[ORM\Column(type: Types::INTEGER)]
    private int $views = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $contactOpens = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $messages = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getListing(): ?Listing
    {
        return $this->listing;
    }

    public function setListing(Listing $listing): self
    {
        $this->listing = $listing;

        return $this;
    }

    public function getDay(): \DateTimeImmutable
    {
        return $this->day;
    }

    public function setDay(\DateTimeImmutable $day): self
    {
        $this->day = $day;

        return $this;
    }

    public function getViews(): int
    {
        return $this->views;
    }

    public function setViews(int $views): self
    {
        $this->views = $views;

        return $this;
    }

    public function getContactOpens(): int
    {
        return $this->contactOpens;
    }

    public function setContactOpens(int $contactOpens): self
    {
        $this->contactOpens = $contactOpens;

        return $this;
    }

    public function getMessages(): int
    {
        return $this->messages;
    }

    public function setMessages(int $messages): self
    {
        $this->messages = $messages;

        return $this;
    }
}
