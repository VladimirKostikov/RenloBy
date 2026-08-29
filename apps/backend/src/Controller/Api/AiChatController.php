<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Exception\ValidationException;
use App\Http\RequestMapper;
use App\Service\AiChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/ai-chat')]
class AiChatController extends AbstractController
{
    public function __construct(
        private readonly AiChatService $aiChatService,
        private readonly RequestMapper $requestMapper,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'api_ai_chat', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        $dto = $this->requestMapper->mapAiChat($this->requestMapper->decodeJson($request));
        $this->assertValid($dto);

        $response = $this->aiChatService->chat($dto);

        return $this->json([
            'reply' => $response->reply,
        ]);
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
