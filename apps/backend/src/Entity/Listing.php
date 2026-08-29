<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\SoftDeletableTrait;
use App\Entity\Trait\TestDataTrait;
use App\Enum\DealType;
use App\Enum\ListingStatus;
use App\Enum\ListingType;
use App\Enum\RentTerm;
use App\Repository\ListingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ListingRepository::class)]
#[ORM\Table(name: 'listings')]
class Listing
{
    use SoftDeletableTrait;
    use TestDataTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, enumType: DealType::class)]
    private DealType $dealType = DealType::Sale;

    #[ORM\Column(length: 20, enumType: ListingType::class)]
    private ListingType $listingType = ListingType::Apartment;

    #[ORM\Column(length: 20, enumType: ListingStatus::class)]
    private ListingStatus $status = ListingStatus::Draft;

    #[ORM\Column(type: Types::INTEGER)]
    private int $price = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $pricePerSqm = 0;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $rooms = 1;

    #[ORM\Column(type: Types::FLOAT)]
    private float $area = 0;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $floor = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $totalFloors = null;

    #[ORM\Column(length: 255)]
    private string $address = '';

    #[ORM\Column(type: Types::FLOAT)]
    private float $latitude = 0;

    #[ORM\Column(type: Types::FLOAT)]
    private float $longitude = 0;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $metroMinutes = null;

    #[ORM\Column]
    private bool $verified = false;

    #[ORM\Column]
    private bool $aiGoodPrice = false;

    #[ORM\Column(length: 10, enumType: RentTerm::class, nullable: true)]
    private ?RentTerm $rentTerm = null;

    #[ORM\Column]
    private bool $hasDeposit = false;

    #[ORM\Column]
    private bool $utilitiesIncluded = false;

    #[ORM\Column]
    private bool $noCommission = false;

    #[ORM\Column]
    private bool $fromOwner = false;

    #[ORM\Column]
    private bool $hasRenovation = false;

    #[ORM\Column]
    private bool $priceNegotiable = false;

    #[ORM\Column(type: Types::INTEGER)]
    private int $views = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $contactOpens = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $messages = 0;

    #[ORM\Column(type: Types::JSON)]
    private array $images = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $metaTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $metaDescription = null;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $metaKeywords = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $publishedAt;

    #[ORM\ManyToOne(inversedBy: 'listings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'listings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?City $city = null;

    #[ORM\ManyToOne(inversedBy: 'listings')]
    #[ORM\JoinColumn(nullable: true)]
    private ?District $district = null;

    #[ORM\ManyToOne(inversedBy: 'listings')]
    private ?MetroStation $metroStation = null;

    #[ORM\OneToMany(mappedBy: 'listing', targetEntity: Favorite::class, orphanRemoval: true)]
    private Collection $favorites;

    #[ORM\OneToMany(mappedBy: 'listing', targetEntity: Comparison::class, orphanRemoval: true)]
    private Collection $comparisons;

    public function __construct()
    {
        $this->publishedAt = new \DateTimeImmutable();
        $this->favorites = new ArrayCollection();
        $this->comparisons = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDealType(): DealType
    {
        return $this->dealType;
    }

    public function setDealType(DealType $dealType): self
    {
        $this->dealType = $dealType;

        return $this;
    }

    public function getListingType(): ListingType
    {
        return $this->listingType;
    }

    public function setListingType(ListingType $listingType): self
    {
        $this->listingType = $listingType;

        return $this;
    }

    public function getStatus(): ListingStatus
    {
        return $this->status;
    }

    public function setStatus(ListingStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getPricePerSqm(): int
    {
        return $this->pricePerSqm;
    }

    public function setPricePerSqm(int $pricePerSqm): self
    {
        $this->pricePerSqm = $pricePerSqm;

        return $this;
    }

    public function getRooms(): int
    {
        return $this->rooms;
    }

    public function setRooms(int $rooms): self
    {
        $this->rooms = $rooms;

        return $this;
    }

    public function getArea(): float
    {
        return $this->area;
    }

    public function setArea(float $area): self
    {
        $this->area = $area;

        return $this;
    }

    public function getFloor(): ?int
    {
        return $this->floor;
    }

    public function setFloor(?int $floor): self
    {
        $this->floor = $floor;

        return $this;
    }

    public function getTotalFloors(): ?int
    {
        return $this->totalFloors;
    }

    public function setTotalFloors(?int $totalFloors): self
    {
        $this->totalFloors = $totalFloors;

        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getMetroMinutes(): ?int
    {
        return $this->metroMinutes;
    }

    public function setMetroMinutes(?int $metroMinutes): self
    {
        $this->metroMinutes = $metroMinutes;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): self
    {
        $this->verified = $verified;

        return $this;
    }

    public function isAiGoodPrice(): bool
    {
        return $this->aiGoodPrice;
    }

    public function setAiGoodPrice(bool $aiGoodPrice): self
    {
        $this->aiGoodPrice = $aiGoodPrice;

        return $this;
    }

    public function getRentTerm(): ?RentTerm
    {
        return $this->rentTerm;
    }

    public function setRentTerm(?RentTerm $rentTerm): self
    {
        $this->rentTerm = $rentTerm;

        return $this;
    }

    public function hasDeposit(): bool
    {
        return $this->hasDeposit;
    }

    public function setHasDeposit(bool $hasDeposit): self
    {
        $this->hasDeposit = $hasDeposit;

        return $this;
    }

    public function isUtilitiesIncluded(): bool
    {
        return $this->utilitiesIncluded;
    }

    public function setUtilitiesIncluded(bool $utilitiesIncluded): self
    {
        $this->utilitiesIncluded = $utilitiesIncluded;

        return $this;
    }

    public function isNoCommission(): bool
    {
        return $this->noCommission;
    }

    public function setNoCommission(bool $noCommission): self
    {
        $this->noCommission = $noCommission;

        return $this;
    }

    public function isFromOwner(): bool
    {
        return $this->fromOwner;
    }

    public function setFromOwner(bool $fromOwner): self
    {
        $this->fromOwner = $fromOwner;

        return $this;
    }

    public function hasRenovation(): bool
    {
        return $this->hasRenovation;
    }

    public function setHasRenovation(bool $hasRenovation): self
    {
        $this->hasRenovation = $hasRenovation;

        return $this;
    }

    public function isPriceNegotiable(): bool
    {
        return $this->priceNegotiable;
    }

    public function setPriceNegotiable(bool $priceNegotiable): self
    {
        $this->priceNegotiable = $priceNegotiable;

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

    public function getImages(): array
    {
        return $this->images;
    }

    public function setImages(array $images): self
    {
        $this->images = $images;

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

    public function getMetaKeywords(): ?string
    {
        return $this->metaKeywords;
    }

    public function setMetaKeywords(?string $metaKeywords): self
    {
        $this->metaKeywords = $metaKeywords;

        return $this;
    }

    public function getPublishedAt(): \DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(\DateTimeImmutable $publishedAt): self
    {
        $this->publishedAt = $publishedAt;

        return $this;
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

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function setCity(?City $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getDistrict(): ?District
    {
        return $this->district;
    }

    public function setDistrict(?District $district): self
    {
        $this->district = $district;

        return $this;
    }

    public function getMetroStation(): ?MetroStation
    {
        return $this->metroStation;
    }

    public function setMetroStation(?MetroStation $metroStation): self
    {
        $this->metroStation = $metroStation;

        return $this;
    }

    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function getComparisons(): Collection
    {
        return $this->comparisons;
    }
}
