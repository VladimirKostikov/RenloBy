<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\RequestMapper;
use App\Service\ArticleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/articles')]
#[IsGranted('ROLE_ADMIN')]
class ArticleAdminController extends AbstractController
{
    public function __construct(
        private readonly ArticleService $articleService,
        private readonly RequestMapper $requestMapper,
    ) {
    }

    #[Route('', name: 'admin_articles_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->articleService->listAll());
    }

    #[Route('/{id}', name: 'admin_articles_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->articleService->get($id));
    }

    #[Route('', name: 'admin_articles_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $article = $this->articleService->create(
            $this->requestMapper->mapCreateArticle($this->requestMapper->decodeJson($request))
        );

        return $this->json($article, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'admin_articles_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        return $this->json(
            $this->articleService->update($id, $this->requestMapper->mapUpdateArticle($this->requestMapper->decodeJson($request)))
        );
    }

    #[Route('/{id}', name: 'admin_articles_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $this->articleService->delete($id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
