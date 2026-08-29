<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Exception\ValidationException;
use App\Service\ArticleImageCatalog;
use App\Service\MediaUploadService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class MediaUploadServiceTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/renlo-upload-test-' . bin2hex(random_bytes(4));
        mkdir($this->projectDir . '/public', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->projectDir);
    }

    public function testUploadStoresImageUnderUploads(): void
    {
        $service = new MediaUploadService($this->projectDir);
        $source = $this->projectDir . '/source.jpg';
        file_put_contents($source, $this->jpegBinary());

        $uploaded = new UploadedFile($source, 'cover.jpg', 'image/jpeg', null, true);
        $result = $service->upload($uploaded);

        self::assertSame('image', $result->type);
        self::assertSame('image/jpeg', $result->mimeType);
        self::assertStringStartsWith('/uploads/articles/', $result->url);
        self::assertFileExists($this->projectDir . '/public' . $result->url);
    }

    public function testUploadListingStoresUnderListings(): void
    {
        $service = new MediaUploadService($this->projectDir);
        $source = $this->projectDir . '/listing.jpg';
        file_put_contents($source, $this->jpegBinary());

        $uploaded = new UploadedFile($source, 'photo.jpg', 'image/jpeg', null, true);
        $result = $service->uploadListing($uploaded);

        self::assertSame('image', $result->type);
        self::assertStringStartsWith('/uploads/listings/', $result->url);
        self::assertFileExists($this->projectDir . '/public' . $result->url);
    }

    public function testUploadRejectsUnsupportedMime(): void
    {
        $service = new MediaUploadService($this->projectDir);
        $source = $this->projectDir . '/evil.txt';
        file_put_contents($source, 'not an image');
        $uploaded = new UploadedFile($source, 'evil.txt', 'text/plain', null, true);

        $this->expectException(ValidationException::class);
        $service->upload($uploaded);
    }

    public function testUploadRejectsFilesOver50Mb(): void
    {
        $service = new MediaUploadService($this->projectDir);
        $uploaded = $this->createMock(UploadedFile::class);
        $uploaded->method('isValid')->willReturn(true);
        $uploaded->method('getSize')->willReturn(MediaUploadService::MAX_FILE_BYTES + 1);
        $uploaded->method('getMimeType')->willReturn('image/jpeg');

        try {
            $service->upload($uploaded);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame('validation.media_file_too_large', $exception->fields['file'] ?? null);
        }
    }

    public function testNormalizeCoverImageAllowsHttpsAndUploads(): void
    {
        $service = new MediaUploadService($this->projectDir);

        self::assertSame(
            'https://images.unsplash.com/photo.jpg',
            $service->normalizeCoverImage('https://images.unsplash.com/photo.jpg'),
        );
        self::assertSame(
            '/uploads/articles/2026/07/a.jpg',
            $service->normalizeCoverImage('/uploads/articles/2026/07/a.jpg'),
        );
        self::assertNull($service->normalizeCoverImage(''));
    }

    public function testSanitizeMediaItemsFiltersInvalid(): void
    {
        $service = new MediaUploadService($this->projectDir);
        $items = $service->sanitizeMediaItems([
            ['url' => 'https://cdn.example.com/a.jpg', 'type' => 'image'],
            ['url' => 'javascript:alert(1)', 'type' => 'image'],
            ['url' => '/uploads/../secret', 'type' => 'image'],
            ['url' => '/uploads/articles/clip.mp4', 'type' => 'video'],
        ]);

        self::assertCount(2, $items);
        self::assertSame('image', $items[0]['type']);
        self::assertSame('video', $items[1]['type']);
    }

    public function testArticleImageCatalogReturnsCovers(): void
    {
        $catalog = new ArticleImageCatalog();
        self::assertStringContainsString('unsplash.com', $catalog->coverForIndex(0));
        self::assertCount(2, $catalog->galleryForIndex(0));
    }

    private function jpegBinary(): string
    {
        $decoded = base64_decode(
            '/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBxISEhUSEhIVFhUVFRUVFRUVFRUWFxUVFRUYHSggGBolGxUVITEhJSkrLi4uFx8zODMtNygtLisBCgoKDg0OGxAQGy0lHyUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLf/AABEIAAEAAQMBIgACEQEDEQH/xAAbAAACAwEBAQAAAAAAAAAAAAADBAECBQYAB//EADYQAAIBAwMCBAMFBQEAAAAAAAECAwAEEQUSITFBEyJRYXGBBjKRoRQjQrHB0fAVYnLh8SQz/8QAGQEAAwEBAQAAAAAAAAAAAAAAAAECAwQF/8QAIhEAAgICAgIDAQEAAAAAAAAAAAECERIhAzFBUQQiYZHwMv/aAAwDAQACEQMRAD8A9o0rSlKUAFFFFABRRRQAUUUUAFFFFAH/2Q==',
            true,
        );

        self::assertNotFalse($decoded);

        return $decoded;
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
