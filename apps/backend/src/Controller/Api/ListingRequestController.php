<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Exception\ValidationException;
use App\Http\RequestMapper;
use App\Service\ListingRequestService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/listings')]
class ListingRequestController extends AbstractController
{
    public function __construct(
        private readonly ListingRequestService $listingRequestService,
        private readonly RequestMapper $requestMapper,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/{id}/requests', name: 'api_listing_requests_create', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function create(int $id, Request $request, #[CurrentUser] ?User $user = null): JsonResponse
    {
        $dto = $this->requestMapper->mapCreateListingRequest($this->requestMapper->decodeJson($request));
        $this->assertValid($dto);

        return $this->json(
            $this->listingRequestService->createForListing($id, $dto, $user),
            Response::HTTP_CREATED,
        );
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
