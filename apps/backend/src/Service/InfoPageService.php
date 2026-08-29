<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\InfoPage\CreateInfoPageRequest;
use App\Dto\InfoPage\InfoPageResponse;
use App\Dto\InfoPage\UpdateInfoPageRequest;
use App\Entity\InfoPage;
use App\Exception\ResourceNotFoundException;
use App\Http\ApiErrorCode;
use App\Repository\InfoPageRepository;
use Doctrine\ORM\EntityManagerInterface;

class InfoPageService
{
    public function __construct(
        private readonly InfoPageRepository $infoPageRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function list(): array
    {
        return array_map(
            fn (InfoPage $page) => InfoPageResponse::fromEntity($page),
            $this->infoPageRepository->findBy([], ['sortOrder' => 'ASC', 'title' => 'ASC'])
        );
    }

    public function get(int $id): InfoPageResponse
    {
        return InfoPageResponse::fromEntity($this->findEntity($id));
    }

    public function getBySlug(string $slug): InfoPageResponse
    {
        $page = $this->infoPageRepository->findBySlug($slug);
        if (!$page instanceof InfoPage) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_INFO_PAGE);
        }

        return InfoPageResponse::fromEntity($page);
    }

    public function create(CreateInfoPageRequest $request): InfoPageResponse
    {
        $page = (new InfoPage())
            ->setSlug($request->slug)
            ->setTitle($request->title)
            ->setBody($request->body)
            ->setCategory($request->category)
            ->setImportantNote($request->importantNote)
            ->setFaqItems($this->normalizeFaqItems($request->faqItems))
            ->setSortOrder($request->sortOrder)
            ->setMetaTitle($this->normalizeNullableText($request->metaTitle))
            ->setMetaDescription($this->normalizeNullableText($request->metaDescription));

        if ($request->isTest !== null) {
            $page->setIsTest($request->isTest);
        }

        $this->entityManager->persist($page);
        $this->entityManager->flush();

        return InfoPageResponse::fromEntity($page);
    }

    public function update(int $id, UpdateInfoPageRequest $request): InfoPageResponse
    {
        $page = $this->findEntity($id);

        if ($request->slug !== null) {
            $page->setSlug($request->slug);
        }
        if ($request->title !== null) {
            $page->setTitle($request->title);
        }
        if ($request->body !== null) {
            $page->setBody($request->body);
        }
        if ($request->category !== null) {
            $page->setCategory($request->category);
        }
        if ($request->importantNote !== null) {
            $page->setImportantNote($request->importantNote !== '' ? $request->importantNote : null);
        }
        if ($request->faqItems !== null) {
            $page->setFaqItems($this->normalizeFaqItems($request->faqItems));
        }
        if ($request->sortOrder !== null) {
            $page->setSortOrder($request->sortOrder);
        }
        if ($request->metaTitle !== null) {
            $page->setMetaTitle($this->normalizeNullableText($request->metaTitle));
        }
        if ($request->metaDescription !== null) {
            $page->setMetaDescription($this->normalizeNullableText($request->metaDescription));
        }
        if ($request->isTest !== null) {
            $page->setIsTest($request->isTest);
        }

        $page->touch();
        $this->entityManager->flush();

        return InfoPageResponse::fromEntity($page);
    }

    public function delete(int $id): void
    {
        $page = $this->findEntity($id);
        $page->softDelete();
        $this->entityManager->flush();
    }

    public function findEntity(int $id): InfoPage
    {
        $page = $this->infoPageRepository->find($id);
        if (!$page instanceof InfoPage) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_INFO_PAGE);
        }

        return $page;
    }

    private function normalizeNullableText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function normalizeFaqItems(array $faqItems): array
    {
        $normalized = [];

        foreach ($faqItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            $question = trim((string) ($item['question'] ?? ''));
            $answer = trim((string) ($item['answer'] ?? ''));

            if ($question === '' && $answer === '') {
                continue;
            }

            $normalized[] = [
                'question' => $question,
                'answer' => $answer,
            ];
        }

        return $normalized;
    }
}
