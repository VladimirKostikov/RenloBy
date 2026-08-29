<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\HeadSnippet\CreateHeadSnippetRequest;
use App\Dto\HeadSnippet\HeadSnippetResponse;
use App\Dto\HeadSnippet\PublicHeadSnippetResponse;
use App\Dto\HeadSnippet\UpdateHeadSnippetRequest;
use App\Entity\HeadSnippet;
use App\Exception\ResourceNotFoundException;
use App\Http\ApiErrorCode;
use App\Repository\HeadSnippetRepository;
use Doctrine\ORM\EntityManagerInterface;

class HeadSnippetService
{
    private const MAX_CODE_LENGTH = 100000;

    public function __construct(
        private readonly HeadSnippetRepository $headSnippetRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return list<HeadSnippetResponse>
     */
    public function list(): array
    {
        return array_map(
            static fn (HeadSnippet $snippet) => HeadSnippetResponse::fromEntity($snippet),
            $this->headSnippetRepository->findAllOrdered()
        );
    }

    /**
     * @return list<PublicHeadSnippetResponse>
     */
    public function listPublicEnabled(): array
    {
        return array_map(
            static fn (HeadSnippet $snippet) => new PublicHeadSnippetResponse($snippet->getCode()),
            $this->headSnippetRepository->findEnabledOrdered()
        );
    }

    public function get(int $id): HeadSnippetResponse
    {
        return HeadSnippetResponse::fromEntity($this->findEntity($id));
    }

    public function create(CreateHeadSnippetRequest $request): HeadSnippetResponse
    {
        $snippet = (new HeadSnippet())
            ->setName(trim($request->name))
            ->setCode($this->normalizeCode($request->code))
            ->setIsEnabled($request->isEnabled)
            ->setSortOrder($request->sortOrder)
            ->setIsTest($request->isTest ?? false);

        $this->entityManager->persist($snippet);
        $this->entityManager->flush();

        return HeadSnippetResponse::fromEntity($snippet);
    }

    public function update(int $id, UpdateHeadSnippetRequest $request): HeadSnippetResponse
    {
        $snippet = $this->findEntity($id);

        if ($request->name !== null) {
            $snippet->setName(trim($request->name));
        }
        if ($request->code !== null) {
            $snippet->setCode($this->normalizeCode($request->code));
        }
        if ($request->isEnabled !== null) {
            $snippet->setIsEnabled($request->isEnabled);
        }
        if ($request->sortOrder !== null) {
            $snippet->setSortOrder($request->sortOrder);
        }
        if ($request->isTest !== null) {
            $snippet->setIsTest($request->isTest);
        }

        $this->entityManager->flush();

        return HeadSnippetResponse::fromEntity($snippet);
    }

    public function delete(int $id): void
    {
        $snippet = $this->findEntity($id);
        $snippet->softDelete();
        $this->entityManager->flush();
    }

    public function findEntity(int $id): HeadSnippet
    {
        $snippet = $this->headSnippetRepository->find($id);
        if (!$snippet instanceof HeadSnippet) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_HEAD_SNIPPET);
        }

        return $snippet;
    }

    private function normalizeCode(string $code): string
    {
        $trimmed = trim($code);
        if ($trimmed === '' || mb_strlen($trimmed) > self::MAX_CODE_LENGTH) {
            throw new \App\Exception\ValidationException(['code' => ApiErrorCode::VALIDATION_FAILED]);
        }

        return $trimmed;
    }
}
