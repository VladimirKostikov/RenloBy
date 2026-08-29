<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\SeoMeta\CreateSeoMetaRequest;
use App\Dto\SeoMeta\SeoMetaResponse;
use App\Dto\SeoMeta\UpdateSeoMetaRequest;
use App\Entity\SeoMeta;
use App\Exception\ResourceNotFoundException;
use App\Http\ApiErrorCode;
use App\Repository\SeoMetaRepository;
use Doctrine\ORM\EntityManagerInterface;

class SeoMetaService
{
    private const ALLOWED_LOCALES = ['ru', 'en'];

    public function __construct(
        private readonly SeoMetaRepository $seoMetaRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return list<SeoMetaResponse>
     */
    public function list(?string $locale = null): array
    {
        $items = $locale !== null && $locale !== ''
            ? $this->seoMetaRepository->findByLocale($locale)
            : $this->seoMetaRepository->findBy([], ['pageKey' => 'ASC', 'locale' => 'ASC']);

        return array_map(
            static fn (SeoMeta $meta) => SeoMetaResponse::fromEntity($meta),
            $items
        );
    }

    public function get(int $id): SeoMetaResponse
    {
        return SeoMetaResponse::fromEntity($this->findEntity($id));
    }

    public function create(CreateSeoMetaRequest $request): SeoMetaResponse
    {
        $meta = (new SeoMeta())
            ->setPageKey($this->normalizePageKey($request->pageKey))
            ->setLocale($this->normalizeLocale($request->locale))
            ->setTitle(trim($request->title))
            ->setDescription(trim($request->description))
            ->setH1($this->normalizeH1($request->h1))
            ->setKeywords($this->normalizeKeywords($request->keywords));

        if ($request->isTest !== null) {
            $meta->setIsTest($request->isTest);
        }

        $this->entityManager->persist($meta);
        $this->entityManager->flush();

        return SeoMetaResponse::fromEntity($meta);
    }

    public function update(int $id, UpdateSeoMetaRequest $request): SeoMetaResponse
    {
        $meta = $this->findEntity($id);

        if ($request->pageKey !== null) {
            $meta->setPageKey($this->normalizePageKey($request->pageKey));
        }
        if ($request->locale !== null) {
            $meta->setLocale($this->normalizeLocale($request->locale));
        }
        if ($request->title !== null) {
            $meta->setTitle(trim($request->title));
        }
        if ($request->description !== null) {
            $meta->setDescription(trim($request->description));
        }
        if ($request->h1 !== null) {
            $meta->setH1($this->normalizeH1($request->h1));
        }
        if ($request->keywords !== null) {
            $meta->setKeywords($this->normalizeKeywords($request->keywords));
        }
        if ($request->isTest !== null) {
            $meta->setIsTest($request->isTest);
        }

        $this->entityManager->flush();

        return SeoMetaResponse::fromEntity($meta);
    }

    public function delete(int $id): void
    {
        $meta = $this->findEntity($id);
        $meta->softDelete();
        $this->entityManager->flush();
    }

    public function findEntity(int $id): SeoMeta
    {
        $meta = $this->seoMetaRepository->find($id);
        if (!$meta instanceof SeoMeta) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_SEO_META);
        }

        return $meta;
    }

    private function normalizePageKey(string $pageKey): string
    {
        return trim($pageKey);
    }

    private function normalizeLocale(string $locale): string
    {
        $normalized = strtolower(trim($locale));
        if (!in_array($normalized, self::ALLOWED_LOCALES, true)) {
            return 'ru';
        }

        return $normalized;
    }

    private function normalizeH1(?string $h1): ?string
    {
        if ($h1 === null) {
            return null;
        }

        $trimmed = trim($h1);

        return $trimmed === '' ? null : $trimmed;
    }

    private function normalizeKeywords(?string $keywords): ?string
    {
        if ($keywords === null) {
            return null;
        }

        $trimmed = trim(preg_replace('/\s+/', ' ', $keywords) ?? '');
        if ($trimmed === '') {
            return null;
        }

        return mb_substr($trimmed, 0, 512);
    }
}
