<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Exception\ValidationException;
use App\Service\MediaUploadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/media')]
#[IsGranted('ROLE_ADMIN')]
class MediaUploadController extends AbstractController
{
    public function __construct(
        private readonly MediaUploadService $mediaUploadService,
    ) {
    }

    #[Route('/upload', name: 'admin_media_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            throw new ValidationException(['file' => 'validation.failed']);
        }

        $raw = $request->query->get('isTest', $request->headers->get('X-Admin-Test-Mode', '0'));
        $isTest = filter_var($raw, FILTER_VALIDATE_BOOLEAN);
        $user = $this->getUser();
        $uploadedBy = $user instanceof \App\Entity\User ? $user : null;

        return $this->json(
            $this->mediaUploadService->upload($file, $isTest, $uploadedBy),
            Response::HTTP_CREATED,
        );
    }
}
