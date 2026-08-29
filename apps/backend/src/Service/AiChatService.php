<?php

declare(strict_types=1);

namespace App\Service;

use App\Ai\DeepSeekChatClient;
use App\Ai\LocalAiChatFallback;
use App\Dto\AiChat\AiChatRequest;
use App\Dto\AiChat\AiChatResponse;
use App\Exception\ServiceUnavailableException;
use Psr\Log\LoggerInterface;

final class AiChatService
{
    private const SYSTEM_PROMPT_RU = <<<'PROMPT'
Ты ИИ-консультант сайта Renlo (DonMap) - агрегатора покупки, продажи и аренды квартир в Беларуси.
Отвечай на русском, кратко и по делу. Помогай с выбором жилья, фильтрами (город, район, комнаты, цена, аренда/продажа), объясняй, как пользоваться сайтом.
Не выдумывай конкретные объявления, цены и адреса, которых нет в сообщении пользователя. Не проси и не принимай оплату. При сложных юридических вопросах советуй обратиться к специалисту.
PROMPT;

    private const SYSTEM_PROMPT_EN = <<<'PROMPT'
You are an AI consultant for Renlo (DonMap), a Belarus real-estate aggregator for buying, selling, and renting apartments.
Reply in English, briefly and to the point. Help with housing choice, filters (city, district, rooms, price, rent/sale), and how to use the site.
Do not invent specific listings, prices, or addresses that are not in the user message. Do not ask for or accept payment. For complex legal questions, suggest contacting a specialist.
PROMPT;

    public function __construct(
        private readonly DeepSeekChatClient $deepSeekChatClient,
        private readonly LocalAiChatFallback $localAiChatFallback,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function chat(AiChatRequest $request): AiChatResponse
    {
        $systemPrompt = $request->locale === 'en'
            ? self::SYSTEM_PROMPT_EN
            : self::SYSTEM_PROMPT_RU;

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach ($request->history as $item) {
            $messages[] = [
                'role' => $item->role,
                'content' => $item->content,
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $request->message,
        ];

        try {
            return new AiChatResponse($this->deepSeekChatClient->complete($messages));
        } catch (ServiceUnavailableException $exception) {
            $this->logger->warning('AI chat fallback used', [
                'reason' => $exception->getMessage(),
            ]);

            return new AiChatResponse(
                $this->localAiChatFallback->reply($request->message, $request->locale),
            );
        }
    }
}
