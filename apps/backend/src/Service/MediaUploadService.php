<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Media\MediaUploadResponse;
use App\Entity\User;
use App\Exception\ValidationException;
use App\Http\ApiErrorCode;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaUploadService
{
    public const MAX_FILE_BYTES = 15_728_640;
    private const MAX_MEDIA_ITEMS = 12;

    private const IMAGE_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    private const VIDEO_MIME = [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
    ];

    public function __construct(
        private readonly string $projectDir,
        private readonly ?MediaFileService $mediaFileService = null,
    ) {
    }

    public function upload(UploadedFile $file, bool $isTest = false, ?User $uploadedBy = null): MediaUploadResponse
    {
        $result = $this->store($file, 'uploads/articles/' . date('Y/m'), true);
        $this->recordUpload($result, 'article', $uploadedBy, $isTest, $file->getClientOriginalName());

        return $result;
    }

    public function uploadListing(UploadedFile $file, ?User $uploadedBy = null): MediaUploadResponse
    {
        $result = $this->store($file, 'uploads/listings/' . date('Y/m'), false);
        $this->recordUpload(
            $result,
            'listing',
            $uploadedBy,
            $uploadedBy?->isTest() ?? false,
            $file->getClientOriginalName(),
        );

        return $result;
    }

    public function uploadAvatar(UploadedFile $file, ?User $uploadedBy = null, ?bool $isTest = null): MediaUploadResponse
    {
        if (!$file->isValid()) {
            throw new ValidationException(['file' => 'validation.failed']);
        }

        if ($file->getSize() > 5_242_880) {
            throw new ValidationException(['file' => 'validation.media_file_too_large']);
        }

        $mime = strtolower((string) $file->getMimeType());
        if (!isset(self::IMAGE_MIME[$mime])) {
            throw new ValidationException(['file' => ApiErrorCode::VALIDATION_PHOTO_INVALID]);
        }

        $result = $this->store($file, 'uploads/avatars/' . date('Y/m'), false);
        $resolvedTest = $isTest ?? ($uploadedBy?->isTest() ?? false);
        $this->recordUpload($result, 'avatar', $uploadedBy, $resolvedTest, $file->getClientOriginalName());

        return $result;
    }

    public function normalizeAvatarUrl(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (!$this->isAllowedMediaUrl($trimmed)) {
            throw new ValidationException(['photo' => ApiErrorCode::VALIDATION_PHOTO_INVALID]);
        }

        return $trimmed;
    }

    private function store(UploadedFile $file, string $relativeDir, bool $allowVideo): MediaUploadResponse
    {
        if (!$file->isValid()) {
            throw new ValidationException(['file' => 'validation.failed']);
        }

        if ($file->getSize() > self::MAX_FILE_BYTES) {
            throw new ValidationException(['file' => 'validation.media_file_too_large']);
        }

        $mime = strtolower((string) $file->getMimeType());
        if ($allowVideo) {
            $type = $this->resolveType($mime);
            $extension = $this->resolveExtension($mime);
        } else {
            if (!isset(self::IMAGE_MIME[$mime])) {
                throw new ValidationException(['file' => ApiErrorCode::VALIDATION_PHOTO_INVALID]);
            }
            $type = 'image';
            $extension = self::IMAGE_MIME[$mime];
        }

        $absoluteDir = $this->projectDir . '/public/' . $relativeDir;
        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            throw new ValidationException(['file' => 'validation.failed']);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $file->move($absoluteDir, $filename);

        return new MediaUploadResponse(
            url: '/' . $relativeDir . '/' . $filename,
            type: $type,
            mimeType: $mime,
            size: (int) filesize($absoluteDir . '/' . $filename),
        );
    }

    private function recordUpload(
        MediaUploadResponse $result,
        string $context,
        ?User $uploadedBy,
        bool $isTest,
        ?string $originalName,
    ): void {
        if ($this->mediaFileService === null) {
            return;
        }

        $this->mediaFileService->record(
            $result,
            $context,
            $uploadedBy,
            $isTest,
            $originalName !== null && $originalName !== '' ? $originalName : null,
        );
    }

    public function normalizeCoverImage(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (!$this->isAllowedMediaUrl($trimmed)) {
            throw new ValidationException(['coverImage' => 'validation.failed']);
        }

        return $trimmed;
    }

    /**
     * @param list<mixed>|null $items
     * @return list<array{url: string, type: string}>
     */
    public function sanitizeMediaItems(?array $items): array
    {
        if ($items === null) {
            return [];
        }

        $result = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $url = isset($item['url']) && is_string($item['url']) ? trim($item['url']) : '';
            $type = isset($item['type']) && is_string($item['type']) ? strtolower(trim($item['type'])) : '';

            if ($url === '' || !$this->isAllowedMediaUrl($url)) {
                continue;
            }

            if ($type !== 'image' && $type !== 'video') {
                $type = $this->guessTypeFromUrl($url);
            }

            $result[] = [
                'url' => $url,
                'type' => $type,
            ];

            if (count($result) >= self::MAX_MEDIA_ITEMS) {
                break;
            }
        }

        return $result;
    }

    public function isAllowedMediaUrl(string $url): bool
    {
        if (str_starts_with($url, '/uploads/')) {
            return !str_contains($url, '..') && preg_match('#^/uploads/[a-z0-9/._-]+$#i', $url) === 1;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        return preg_match('#^https?://#i', $url) === 1;
    }

    private function resolveType(string $mime): string
    {
        if (isset(self::IMAGE_MIME[$mime])) {
            return 'image';
        }
        if (isset(self::VIDEO_MIME[$mime])) {
            return 'video';
        }

        throw new ValidationException(['file' => 'validation.failed']);
    }

    private function resolveExtension(string $mime): string
    {
        return self::IMAGE_MIME[$mime] ?? self::VIDEO_MIME[$mime] ?? throw new ValidationException(['file' => 'validation.failed']);
    }

    private function guessTypeFromUrl(string $url): string
    {
        $path = strtolower(parse_url($url, PHP_URL_PATH) ?: $url);
        if (preg_match('/\.(mp4|webm)$/', $path) === 1) {
            return 'video';
        }

        return 'image';
    }
}
