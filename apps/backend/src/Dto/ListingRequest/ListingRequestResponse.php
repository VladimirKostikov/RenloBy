<?php

declare(strict_types=1);

namespace App\Dto\ListingRequest;

use App\Entity\ListingRequest;
use App\Enum\ListingRequestStatus;

readonly class ListingRequestResponse
{
    public function __construct(
        public int $id,
        public int $listingId,
        public ?string $name,
        public string $phone,
        public string $message,
        public ListingRequestStatus $status,
        public string $createdAt,
        public bool $isTest,
        public ?string $listingAddress = null,
    ) {
    }

    public static function fromEntity(ListingRequest $request): self
    {
        return new self(
            $request->getId() ?? 0,
            $request->getListing()?->getId() ?? 0,
            $request->getName(),
            $request->getPhone(),
            $request->getMessage(),
            $request->getStatus(),
            $request->getCreatedAt()->format(\DateTimeInterface::ATOM),
            $request->isTest(),
            $request->getListing()?->getAddress(),
        );
    }
}
