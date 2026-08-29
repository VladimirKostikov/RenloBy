<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\MediaFileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/media-files')]
#[IsGranted('ROLE_ADMIN')]
class MediaFileAdminController extends AbstractController
{
    public function __construct(
        private readonly MediaFileService $mediaFileService,
    ) {
    }

    #[Route('', name: 'admin_media_files_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->mediaFileService->listAll());
    }

    #[Route('/{id}', name: 'admin_media_files_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->mediaFileService->get($id));
    }

    #[Route('/{id}', name: 'admin_media_files_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $this->mediaFileService->delete($id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
