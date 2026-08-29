<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\HeadSnippetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/head-snippets')]
class HeadSnippetController extends AbstractController
{
    public function __construct(
        private readonly HeadSnippetService $headSnippetService,
    ) {
    }

    #[Route('', name: 'api_head_snippets_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->headSnippetService->listPublicEnabled());
    }
}
