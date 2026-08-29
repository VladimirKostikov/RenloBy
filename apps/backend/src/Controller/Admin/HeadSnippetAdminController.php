<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Exception\ValidationException;
use App\Http\RequestMapper;
use App\Service\HeadSnippetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/head-snippets')]
#[IsGranted('ROLE_ADMIN')]
class HeadSnippetAdminController extends AbstractController
{
    public function __construct(
        private readonly HeadSnippetService $headSnippetService,
        private readonly RequestMapper $requestMapper,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'admin_head_snippets_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->headSnippetService->list());
    }

    #[Route('/{id}', name: 'admin_head_snippets_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->headSnippetService->get($id));
    }

    #[Route('', name: 'admin_head_snippets_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dto = $this->requestMapper->mapCreateHeadSnippet($this->requestMapper->decodeJson($request));
        $this->assertValid($dto);

        return $this->json($this->headSnippetService->create($dto), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'admin_head_snippets_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $dto = $this->requestMapper->mapUpdateHeadSnippet($this->requestMapper->decodeJson($request));
        $this->assertValid($dto);

        return $this->json($this->headSnippetService->update($id, $dto));
    }

    #[Route('/{id}', name: 'admin_head_snippets_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $this->headSnippetService->delete($id);

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
