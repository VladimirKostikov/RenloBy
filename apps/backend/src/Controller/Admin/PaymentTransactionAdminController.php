<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\PaymentTransactionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/payment-transactions')]
#[IsGranted('ROLE_ADMIN')]
class PaymentTransactionAdminController extends AbstractController
{
    public function __construct(
        private readonly PaymentTransactionService $paymentTransactionService,
    ) {
    }

    #[Route('', name: 'admin_payment_transactions_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->paymentTransactionService->listAll());
    }

    #[Route('/{id}', name: 'admin_payment_transactions_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->paymentTransactionService->get($id));
    }

    #[Route('/{id}', name: 'admin_payment_transactions_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $this->paymentTransactionService->delete($id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
