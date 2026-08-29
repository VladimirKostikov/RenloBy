<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\RequestMapper;
use App\Service\TariffService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Exception\ValidationException;

#[Route('/admin/tariffs')]
#[IsGranted('ROLE_ADMIN')]
class TariffAdminController extends AbstractController
{
    public function __construct(
        private readonly TariffService $tariffService,
        private readonly RequestMapper $requestMapper,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'admin_tariffs_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->tariffService->list());
    }

    #[Route('/{id}', name: 'admin_tariffs_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->tariffService->get($id));
    }

    #[Route('/{id}', name: 'admin_tariffs_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $dto = $this->requestMapper->mapUpdateTariff($this->requestMapper->decodeJson($request));
        $this->assertValid($dto);

        return $this->json($this->tariffService->update($id, $dto));
    }

    #[Route('/{id}', name: 'admin_tariffs_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $this->tariffService->delete($id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function assertValid(object $dto): void
    {
        $violations = $this->validator->validate($dto);
        if (count($violations) === 0) {
            return;
        }

        $fields = [];
        /** @var ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            $property = (string) $violation->getPropertyPath();
            if ($property !== '' && !isset($fields[$property])) {
                $fields[$property] = (string) $violation->getMessage();
            }
        }

        throw new ValidationException($fields);
    }
}
