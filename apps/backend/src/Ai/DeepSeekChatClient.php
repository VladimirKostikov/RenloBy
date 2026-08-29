<?php

declare(strict_types=1);

namespace App\Ai;

use App\Exception\ServiceUnavailableException;
use App\Http\ApiErrorCode;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class DeepSeekChatClient
{
    private const API_URL = 'https://api.deepseek.com/chat/completions';
    private const MODEL = 'deepseek-chat';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $apiKey,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * @param list<array{role: string, content: string}> $messages
     */
    public function complete(array $messages): string
    {
        if (!$this->isConfigured()) {
            throw new ServiceUnavailableException(ApiErrorCode::AI_CHAT_UNAVAILABLE);
        }

        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => self::MODEL,
                    'messages' => $messages,
                    'temperature' => 0.6,
                    'max_tokens' => 1024,
                ],
                'timeout' => 45,
            ]);

            $status = $response->getStatusCode();
            $payload = $response->toArray(false);

            if ($status < 200 || $status >= 300) {
                $this->logger->warning('DeepSeek chat failed', [
                    'status' => $status,
                ]);
                throw new ServiceUnavailableException(ApiErrorCode::AI_CHAT_UNAVAILABLE);
            }

            $content = $payload['choices'][0]['message']['content'] ?? null;
            if (!is_string($content) || trim($content) === '') {
                throw new ServiceUnavailableException(ApiErrorCode::AI_CHAT_UNAVAILABLE);
            }

            return trim($content);
        } catch (TransportExceptionInterface $e) {
            $this->logger->warning('DeepSeek transport error', [
                'error' => $e->getMessage(),
            ]);
            throw new ServiceUnavailableException(ApiErrorCode::AI_CHAT_UNAVAILABLE);
        }
    }
}
