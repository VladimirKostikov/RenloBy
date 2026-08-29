<?php

declare(strict_types=1);

namespace App\Dto\Media;

use App\Entity\MediaFile;

readonly class MediaFileResponse
{
    public function __construct(
        public int $id,
        public string $url,
        public string $type,
        public string $mimeType,
        public int $size,
        public string $context,
        public ?int $uploadedById,
        public ?string $uploadedByEmail,
        public ?string $originalName,
        public bool $isTest,
        public string $createdAt,
    ) {
    }

    public static function fromEntity(MediaFile $file): self
    {
        $uploadedBy = $file->getUploadedBy();

        return new self(
            $file->getId() ?? 0,
            $file->getUrl(),
            $file->getType(),
            $file->getMimeType(),
            $file->getSize(),
            $file->getContext(),
            $uploadedBy?->getId(),
            $uploadedBy?->getEmail(),
            $file->getOriginalName(),
            $file->isTest(),
            $file->getCreatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
