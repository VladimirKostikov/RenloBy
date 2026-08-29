<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\ArticleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/articles')]
class ArticleController extends AbstractController
{
    public function __construct(
        private readonly ArticleService $articleService,
    ) {
    }

    #[Route('', name: 'api_articles_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->articleService->listPublished());
    }

    #[Route('/{slug}', name: 'api_articles_show', methods: ['GET'], requirements: ['slug' => '[a-z0-9-]+'])]
    public function show(string $slug): JsonResponse
    {
        return $this->json($this->articleService->getPublishedBySlug($slug));
    }
}
