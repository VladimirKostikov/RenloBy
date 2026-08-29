<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\UserNotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/user-notifications')]
#[IsGranted('ROLE_ADMIN')]
class UserNotificationAdminController extends AbstractController
{
    public function __construct(
        private readonly UserNotificationService $userNotificationService,
    ) {
    }

    #[Route('', name: 'admin_user_notifications_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->userNotificationService->listAdmin());
    }

    #[Route('/{id}', name: 'admin_user_notifications_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->userNotificationService->getAdmin($id));
    }

    #[Route('/{id}', name: 'admin_user_notifications_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $this->userNotificationService->delete($id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
