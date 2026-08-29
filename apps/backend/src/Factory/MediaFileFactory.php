<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\MediaFile;
use App\Entity\User;

class MediaFileFactory
{
    public function create(
        string $url,
        string $type,
        string $mimeType,
        int $size,
        string $context = 'article',
        ?User $uploadedBy = null,
        ?string $originalName = null,
        bool $isTest = true,
        ?\DateTimeImmutable $createdAt = null,
    ): MediaFile {
        $file = (new MediaFile())
            ->setUrl($url)
            ->setType($type)
            ->setMimeType($mimeType)
            ->setSize($size)
            ->setContext($context)
            ->setUploadedBy($uploadedBy)
            ->setOriginalName($originalName)
            ->setIsTest($isTest);

        if ($createdAt instanceof \DateTimeImmutable) {
            $file->setCreatedAt($createdAt);
        }

        return $file;
    }
}
