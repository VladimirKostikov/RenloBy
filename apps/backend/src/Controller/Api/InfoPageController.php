<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\InfoPageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/info-pages')]
class InfoPageController extends AbstractController
{
    public function __construct(
        private readonly InfoPageService $infoPageService,
    ) {
    }

    #[Route('', name: 'api_info_pages_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->infoPageService->list());
    }

    #[Route('/{slug}', name: 'api_info_pages_show', methods: ['GET'], requirements: ['slug' => '[a-z0-9-]+'])]
    public function show(string $slug): JsonResponse
    {
        return $this->json($this->infoPageService->getBySlug($slug));
    }
}
