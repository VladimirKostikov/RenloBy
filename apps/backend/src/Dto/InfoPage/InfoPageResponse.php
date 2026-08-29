<?php

declare(strict_types=1);

namespace App\Dto\InfoPage;

use App\Entity\InfoPage;

readonly class InfoPageResponse
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $title,
        public string $body,
        public string $category,
        public ?string $importantNote,
        public array $faqItems,
        public int $sortOrder,
        public ?string $metaTitle,
        public ?string $metaDescription,
        public string $updatedAt,
        public bool $isTest,
    ) {
    }

    public static function fromEntity(InfoPage $page): self
    {
        $faqItems = array_map(
            static fn (array $item): InfoFaqItemResponse => new InfoFaqItemResponse(
                question: (string) ($item['question'] ?? ''),
                answer: (string) ($item['answer'] ?? ''),
            ),
            $page->getFaqItems(),
        );

        return new self(
            $page->getId() ?? 0,
            $page->getSlug(),
            $page->getTitle(),
            $page->getBody(),
            $page->getCategory()->value,
            $page->getImportantNote(),
            $faqItems,
            $page->getSortOrder(),
            $page->getMetaTitle(),
            $page->getMetaDescription(),
            $page->getUpdatedAt()->format('Y-m-d'),
            $page->isTest(),
        );
    }
}
