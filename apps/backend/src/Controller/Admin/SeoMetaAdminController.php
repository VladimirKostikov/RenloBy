<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\RequestMapper;
use App\Service\SeoMetaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/seo-meta')]
#[IsGranted('ROLE_ADMIN')]
class SeoMetaAdminController extends AbstractController
{
    public function __construct(
        private readonly SeoMetaService $seoMetaService,
        private readonly RequestMapper $requestMapper,
    ) {
    }

    #[Route('', name: 'admin_seo_meta_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $locale = $request->query->get('locale');

        return $this->json($this->seoMetaService->list(is_string($locale) ? $locale : null));
    }

    #[Route('/{id}', name: 'admin_seo_meta_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->seoMetaService->get($id));
    }

    #[Route('', name: 'admin_seo_meta_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $meta = $this->seoMetaService->create(
            $this->requestMapper->mapCreateSeoMeta($this->requestMapper->decodeJson($request))
        );

        return $this->json($meta, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'admin_seo_meta_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        return $this->json(
            $this->seoMetaService->update($id, $this->requestMapper->mapUpdateSeoMeta($this->requestMapper->decodeJson($request)))
        );
    }

    #[Route('/{id}', name: 'admin_seo_meta_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $this->seoMetaService->delete($id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
