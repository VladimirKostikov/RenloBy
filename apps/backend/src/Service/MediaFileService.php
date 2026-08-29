<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Media\MediaFileResponse;
use App\Dto\Media\MediaUploadResponse;
use App\Entity\Article;
use App\Entity\MediaFile;
use App\Entity\User;
use App\Exception\ResourceNotFoundException;
use App\Factory\MediaFileFactory;
use App\Http\ApiErrorCode;
use App\Repository\ArticleRepository;
use App\Repository\MediaFileRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class MediaFileService
{
    private const ALLOWED_CONTEXTS = ['avatar', 'article', 'listing'];

    public function __construct(
        private readonly MediaFileRepository $mediaFileRepository,
        private readonly MediaFileFactory $mediaFileFactory,
        private readonly UserRepository $userRepository,
        private readonly ArticleRepository $articleRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $projectDir,
    ) {
    }

    /**
     * @return list<MediaFileResponse>
     */
    public function listAll(): array
    {
        $this->syncFromDisk();

        return array_map(
            static fn (MediaFile $file) => MediaFileResponse::fromEntity($file),
            $this->mediaFileRepository->findAllOrdered(),
        );
    }

    public function get(int $id): MediaFileResponse
    {
        return MediaFileResponse::fromEntity($this->findEntity($id));
    }

    public function record(
        MediaUploadResponse $upload,
        string $context,
        ?User $uploadedBy = null,
        bool $isTest = false,
        ?string $originalName = null,
    ): MediaFile {
        $context = strtolower(trim($context));
        if (!in_array($context, self::ALLOWED_CONTEXTS, true)) {
            $context = 'article';
        }

        $existing = $this->mediaFileRepository->findOneByUrl($upload->url);
        if ($existing instanceof MediaFile) {
            $existing
                ->setType($upload->type)
                ->setMimeType($upload->mimeType)
                ->setSize($upload->size)
                ->setContext($context)
                ->setIsTest($isTest);
            if ($uploadedBy instanceof User) {
                $existing->setUploadedBy($uploadedBy);
            }
            if ($originalName !== null) {
                $existing->setOriginalName($originalName);
            }
            $this->entityManager->flush();

            return $existing;
        }

        $file = $this->mediaFileFactory->create(
            url: $upload->url,
            type: $upload->type,
            mimeType: $upload->mimeType,
            size: $upload->size,
            context: $context,
            uploadedBy: $uploadedBy,
            originalName: $originalName,
            isTest: $isTest,
        );
        $this->entityManager->persist($file);
        $this->entityManager->flush();

        return $file;
    }

    public function delete(int $id): void
    {
        $file = $this->findEntity($id);
        $url = $file->getUrl();

        $this->clearReferences($url);
        $this->unlinkUpload($url);
        $file->softDelete();
        $this->entityManager->flush();
    }

    public function findEntity(int $id): MediaFile
    {
        $file = $this->mediaFileRepository->find($id);
        if (!$file instanceof MediaFile) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_MEDIA_FILE);
        }

        return $file;
    }

    private function syncFromDisk(): void
    {
        $uploadsRoot = $this->projectDir . '/public/uploads';
        if (!is_dir($uploadsRoot)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($uploadsRoot, \FilesystemIterator::SKIP_DOTS),
        );

        $created = false;
        /** @var \SplFileInfo $info */
        foreach ($iterator as $info) {
            if (!$info->isFile()) {
                continue;
            }

            $absolute = $info->getPathname();
            $relative = substr($absolute, strlen($this->projectDir . '/public'));
            $relative = str_replace('\\', '/', $relative);
            if (!preg_match('#^/uploads/[a-z0-9/._-]+$#i', $relative)) {
                continue;
            }

            if ($this->mediaFileRepository->findOneByUrl($relative) instanceof MediaFile) {
                continue;
            }

            $mime = mime_content_type($absolute) ?: 'application/octet-stream';
            $type = str_starts_with($mime, 'video/') ? 'video' : 'image';
            $context = match (true) {
                str_contains($relative, '/avatars/') => 'avatar',
                str_contains($relative, '/listings/') => 'listing',
                default => 'article',
            };
            $mtime = $info->getMTime();
            $createdAt = $mtime > 0
                ? (new \DateTimeImmutable())->setTimestamp($mtime)
                : new \DateTimeImmutable();

            $file = $this->mediaFileFactory->create(
                url: $relative,
                type: $type,
                mimeType: $mime,
                size: (int) $info->getSize(),
                context: $context,
                isTest: false,
                createdAt: $createdAt,
            );
            $this->entityManager->persist($file);
            $created = true;
        }

        if ($created) {
            $this->entityManager->flush();
        }
    }

    private function clearReferences(string $url): void
    {
        $users = $this->userRepository->createQueryBuilder('u')
            ->andWhere('u.photo = :url')
            ->setParameter('url', $url)
            ->getQuery()
            ->getResult();

        foreach ($users as $user) {
            if ($user instanceof User) {
                $user->setPhoto(null);
            }
        }

        $articles = $this->articleRepository->findAll();
        foreach ($articles as $article) {
            if (!$article instanceof Article) {
                continue;
            }
            if ($article->getCoverImage() === $url) {
                $article->setCoverImage(null);
            }
            $media = $article->getMedia();
            if ($media === []) {
                continue;
            }
            $filtered = [];
            $changed = false;
            foreach ($media as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $itemUrl = isset($item['url']) && is_string($item['url']) ? $item['url'] : '';
                if ($itemUrl === $url) {
                    $changed = true;
                    continue;
                }
                $filtered[] = $item;
            }
            if ($changed) {
                $article->setMedia($filtered);
            }
        }
    }

    private function unlinkUpload(string $url): void
    {
        $path = $this->resolveUploadPath($url);
        if ($path === null) {
            return;
        }
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function resolveUploadPath(string $url): ?string
    {
        if (!preg_match('#^/uploads/[a-z0-9/._-]+$#i', $url) || str_contains($url, '..')) {
            return null;
        }

        $candidate = $this->projectDir . '/public' . $url;
        $realFile = realpath($candidate);
        $uploadsRoot = realpath($this->projectDir . '/public/uploads');
        if ($realFile === false || $uploadsRoot === false) {
            return is_file($candidate) ? $candidate : null;
        }

        $prefix = $uploadsRoot . DIRECTORY_SEPARATOR;
        if (!str_starts_with($realFile, $prefix)) {
            return null;
        }

        return $realFile;
    }
}
