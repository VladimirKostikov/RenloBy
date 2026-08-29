<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\UserNotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/me/notifications')]
#[IsGranted('ROLE_USER')]
class MeNotificationController extends AbstractController
{
    public function __construct(
        private readonly UserNotificationService $userNotificationService,
    ) {
    }

    #[Route('', name: 'api_me_notifications_index', methods: ['GET'])]
    public function index(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $limit = max(1, min(100, (int) $request->query->get('limit', 50)));

        return $this->json($this->userNotificationService->listForUser($user, $limit));
    }

    #[Route('/unread-count', name: 'api_me_notifications_unread_count', methods: ['GET'])]
    public function unreadCount(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->userNotificationService->unreadCount($user));
    }

    #[Route('/read-all', name: 'api_me_notifications_read_all', methods: ['POST'])]
    public function readAll(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->userNotificationService->markAllRead($user));
    }

    #[Route('/{id}/read', name: 'api_me_notifications_read', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function read(int $id, #[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->userNotificationService->markRead($user, $id));
    }
}
