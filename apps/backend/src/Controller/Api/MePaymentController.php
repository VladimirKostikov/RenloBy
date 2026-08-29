<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Payment\CreatePaymentRequest;
use App\Entity\User;
use App\Exception\ValidationException;
use App\Http\ApiErrorCode;
use App\Http\RequestMapper;
use App\Service\PaymentTransactionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/me/payments')]
#[IsGranted('ROLE_USER')]
class MePaymentController extends AbstractController
{
    public function __construct(
        private readonly PaymentTransactionService $paymentTransactionService,
        private readonly RequestMapper $requestMapper,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'api_me_payments_index', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->paymentTransactionService->listForUser($user));
    }

    #[Route('', name: 'api_me_payments_create', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = $this->requestMapper->decodeJson($request);
        $createRequest = new CreatePaymentRequest(
            amount: (string) ($data['amount'] ?? '0'),
            currency: strtoupper((string) ($data['currency'] ?? 'BYN')),
            description: (string) ($data['description'] ?? ''),
            returnUrl: (string) ($data['returnUrl'] ?? ''),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
            isTest: (bool) ($data['isTest'] ?? false),
        );

        $violations = $this->validator->validate($createRequest);
        if (count($violations) > 0) {
            $errors = [];
            /** @var ConstraintViolationInterface $violation */
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = (string) $violation->getMessage();
            }
            throw new ValidationException($errors !== [] ? $errors : ['request' => ApiErrorCode::VALIDATION_FAILED]);
        }

        $tx = $this->paymentTransactionService->createForUser($user, $createRequest);

        return $this->json($tx, Response::HTTP_CREATED);
    }
}
