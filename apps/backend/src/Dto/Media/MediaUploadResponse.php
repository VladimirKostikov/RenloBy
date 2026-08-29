<?php

declare(strict_types=1);

namespace App\Dto\Media;

readonly class MediaUploadResponse
{
    public function __construct(
        public string $url,
        public string $type,
        public string $mimeType,
        public int $size,
    ) {
    }
}
