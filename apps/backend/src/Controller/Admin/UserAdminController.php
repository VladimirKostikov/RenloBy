<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\RequestMapper;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserAdminController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly RequestMapper $requestMapper,
    ) {
    }

    #[Route('/export-emails', name: 'admin_users_export_emails', methods: ['GET'])]
    public function exportEmails(): Response
    {
        return $this->userService->exportEmailsCsv();
    }

    #[Route('', name: 'admin_users_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->userService->list());
    }

    #[Route('/{id}', name: 'admin_users_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->userService->get($id));
    }

    #[Route('', name: 'admin_users_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->userService->create(
            $this->requestMapper->mapCreateUser($this->requestMapper->decodeJson($request))
        );

        return $this->json($user, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'admin_users_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        return $this->json(
            $this->userService->update($id, $this->requestMapper->mapUpdateUser($this->requestMapper->decodeJson($request)))
        );
    }

    #[Route('/{id}/photo', name: 'admin_users_photo', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function uploadPhoto(int $id, Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            throw new \App\Exception\ValidationException(['file' => 'validation.failed']);
        }

        return $this->json($this->userService->uploadPhoto($id, $file));
    }

    #[Route('/{id}', name: 'admin_users_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $this->userService->delete($id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
