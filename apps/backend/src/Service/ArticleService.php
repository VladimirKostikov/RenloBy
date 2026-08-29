<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Article\ArticleResponse;
use App\Dto\Article\CreateArticleRequest;
use App\Dto\Article\UpdateArticleRequest;
use App\Entity\Article;
use App\Exception\ResourceNotFoundException;
use App\Http\ApiErrorCode;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;

class ArticleService
{
    public function __construct(
        private readonly ArticleRepository $articleRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MediaUploadService $mediaUploadService,
    ) {
    }

    /**
     * @return list<ArticleResponse>
     */
    public function listPublished(): array
    {
        return array_map(
            static fn (Article $article) => ArticleResponse::fromEntity($article),
            $this->articleRepository->findPublishedOrdered(),
        );
    }

    /**
     * @return list<ArticleResponse>
     */
    public function listAll(): array
    {
        return array_map(
            static fn (Article $article) => ArticleResponse::fromEntity($article),
            $this->articleRepository->findAllOrdered(),
        );
    }

    public function get(int $id): ArticleResponse
    {
        return ArticleResponse::fromEntity($this->findEntity($id));
    }

    public function getPublishedBySlug(string $slug): ArticleResponse
    {
        $article = $this->articleRepository->findPublishedBySlug($slug);
        if (!$article instanceof Article) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_ARTICLE);
        }

        return ArticleResponse::fromEntity($article);
    }

    public function create(CreateArticleRequest $request): ArticleResponse
    {
        $article = (new Article())
            ->setSlug($request->slug)
            ->setTitle($request->title)
            ->setExcerpt($request->excerpt)
            ->setBody($request->body)
            ->setCategory($request->category)
            ->setCoverImage($this->mediaUploadService->normalizeCoverImage($request->coverImage))
            ->setMedia($this->mediaUploadService->sanitizeMediaItems($request->media))
            ->setIsPublished($request->isPublished)
            ->setPublishedAt($this->parseDate($request->publishedAt))
            ->setMetaTitle($this->normalizeNullableText($request->metaTitle))
            ->setMetaDescription($this->normalizeNullableText($request->metaDescription))
            ->setIsTest($request->isTest ?? true);

        $this->entityManager->persist($article);
        $this->entityManager->flush();

        return ArticleResponse::fromEntity($article);
    }

    public function update(int $id, UpdateArticleRequest $request): ArticleResponse
    {
        $article = $this->findEntity($id);

        if ($request->slug !== null) {
            $article->setSlug($request->slug);
        }
        if ($request->title !== null) {
            $article->setTitle($request->title);
        }
        if ($request->excerpt !== null) {
            $article->setExcerpt($request->excerpt);
        }
        if ($request->body !== null) {
            $article->setBody($request->body);
        }
        if ($request->category !== null) {
            $article->setCategory($request->category);
        }
        if ($request->coverImage !== null) {
            $article->setCoverImage($this->mediaUploadService->normalizeCoverImage($request->coverImage));
        }
        if ($request->media !== null) {
            $article->setMedia($this->mediaUploadService->sanitizeMediaItems($request->media));
        }
        if ($request->isPublished !== null) {
            $article->setIsPublished($request->isPublished);
        }
        if ($request->publishedAt !== null) {
            $article->setPublishedAt($this->parseDate($request->publishedAt));
        }
        if ($request->metaTitle !== null) {
            $article->setMetaTitle($this->normalizeNullableText($request->metaTitle));
        }
        if ($request->metaDescription !== null) {
            $article->setMetaDescription($this->normalizeNullableText($request->metaDescription));
        }
        if ($request->isTest !== null) {
            $article->setIsTest($request->isTest);
        }

        $article->touch();
        $this->entityManager->flush();

        return ArticleResponse::fromEntity($article);
    }

    public function delete(int $id): void
    {
        $article = $this->findEntity($id);
        $article->softDelete();
        $this->entityManager->flush();
    }

    public function findEntity(int $id): Article
    {
        $article = $this->articleRepository->find($id);
        if (!$article instanceof Article) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_ARTICLE);
        }

        return $article;
    }

    private function normalizeNullableText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function parseDate(string $value): \DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value)
            ?: \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $value);

        if (!$date instanceof \DateTimeImmutable) {
            return new \DateTimeImmutable($value);
        }

        return $date->setTime(0, 0);
    }
}
