<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\SoftDeletableTrait;
use App\Entity\Trait\TestDataTrait;
use App\Repository\CityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CityRepository::class)]
#[ORM\Table(name: 'cities')]
class City
{
    use SoftDeletableTrait;
    use TestDataTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name = '';

    #[ORM\Column(length: 50)]
    private string $slug = '';

    #[ORM\Column(length: 50)]
    private string $regionSlug = '';

    #[ORM\OneToMany(mappedBy: 'city', targetEntity: District::class, orphanRemoval: true)]
    private Collection $districts;

    #[ORM\OneToMany(mappedBy: 'city', targetEntity: MetroStation::class, orphanRemoval: true)]
    private Collection $metroStations;

    #[ORM\OneToMany(mappedBy: 'city', targetEntity: Listing::class)]
    private Collection $listings;

    public function __construct()
    {
        $this->districts = new ArrayCollection();
        $this->metroStations = new ArrayCollection();
        $this->listings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
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

    public function getRegionSlug(): string
    {
        return $this->regionSlug;
    }

    public function setRegionSlug(string $regionSlug): self
    {
        $this->regionSlug = $regionSlug;

        return $this;
    }

    public function getDistricts(): Collection
    {
        return $this->districts;
    }

    public function getMetroStations(): Collection
    {
        return $this->metroStations;
    }

    public function getListings(): Collection
    {
        return $this->listings;
    }
}
