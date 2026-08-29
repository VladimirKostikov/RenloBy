<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Exception\ValidationException;
use App\Http\RequestMapper;
use App\Service\ListingReportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/listings')]
class ListingReportController extends AbstractController
{
    public function __construct(
        private readonly ListingReportService $listingReportService,
        private readonly RequestMapper $requestMapper,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/{id}/reports', name: 'api_listing_reports_create', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function create(int $id, Request $request): JsonResponse
    {
        $dto = $this->requestMapper->mapCreateListingReport($this->requestMapper->decodeJson($request));
        $this->assertValid($dto);

        return $this->json(
            $this->listingReportService->createForListing($id, $dto),
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
