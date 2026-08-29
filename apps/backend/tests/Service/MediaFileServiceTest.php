<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\Media\MediaUploadResponse;
use App\Factory\MediaFileFactory;
use App\Repository\ArticleRepository;
use App\Repository\MediaFileRepository;
use App\Repository\UserRepository;
use App\Service\MediaFileService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class MediaFileServiceTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/renlo-media-file-' . bin2hex(random_bytes(4));
        mkdir($this->projectDir . '/public/uploads/avatars/2026/07', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->projectDir);
    }

    public function testSyncFromDiskRegistersExistingUpload(): void
    {
        $relative = '/uploads/avatars/2026/07/disk.jpg';
        file_put_contents($this->projectDir . '/public' . $relative, 'fake-image');

        $repo = $this->createMock(MediaFileRepository::class);
        $repo->method('findOneByUrl')->willReturn(null);
        $repo->method('findAllOrdered')->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $persisted = [];
        $em->expects($this->once())
            ->method('persist')
            ->willReturnCallback(static function ($entity) use (&$persisted): void {
                $persisted[] = $entity;
            });
        $em->expects($this->once())->method('flush');

        $service = new MediaFileService(
            $repo,
            new MediaFileFactory(),
            $this->createMock(UserRepository::class),
            $this->createMock(ArticleRepository::class),
            $em,
            $this->projectDir,
        );

        $service->listAll();

        self::assertCount(1, $persisted);
        self::assertSame($relative, $persisted[0]->getUrl());
        self::assertSame('avatar', $persisted[0]->getContext());
    }

    public function testRecordPersistsUploadMetadata(): void
    {
        $repo = $this->createMock(MediaFileRepository::class);
        $repo->method('findOneByUrl')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');

        $service = new MediaFileService(
            $repo,
            new MediaFileFactory(),
            $this->createMock(UserRepository::class),
            $this->createMock(ArticleRepository::class),
            $em,
            $this->projectDir,
        );

        $file = $service->record(
            new MediaUploadResponse('/uploads/articles/2026/07/a.jpg', 'image', 'image/jpeg', 120),
            'article',
            null,
            true,
            'a.jpg',
        );

        self::assertSame('/uploads/articles/2026/07/a.jpg', $file->getUrl());
        self::assertSame('article', $file->getContext());
        self::assertTrue($file->isTest());
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
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
