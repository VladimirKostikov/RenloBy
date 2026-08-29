<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\SeoMetaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/seo-meta')]
class SeoMetaController extends AbstractController
{
    public function __construct(
        private readonly SeoMetaService $seoMetaService,
    ) {
    }

    #[Route('', name: 'api_seo_meta_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $locale = $request->query->get('locale', 'ru');

        return $this->json($this->seoMetaService->list(is_string($locale) ? $locale : 'ru'));
    }
}
