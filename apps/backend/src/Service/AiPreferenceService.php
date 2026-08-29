<?php

declare(strict_types=1);

namespace App\Service;

use App\Ai\DeepSeekChatClient;
use App\Dto\AiAssistant\AiPreferenceAnswers;
use App\Dto\AiAssistant\AiPreferenceResponse;
use App\Dto\AiAssistant\CreateAiPreferenceRequest;
use App\Dto\Collection\CollectionOwner;
use App\Dto\Listing\ListingSearchRequest;
use App\Entity\AiPreference;
use App\Entity\User;
use App\Enum\DealType;
use App\Exception\ResourceNotFoundException;
use App\Exception\ServiceUnavailableException;
use App\Factory\AiPreferenceFactory;
use App\Http\ApiErrorCode;
use App\Repository\AiPreferenceRepository;
use App\Repository\ListingRepository;
use Doctrine\ORM\EntityManagerInterface;

class AiPreferenceService
{
    private const MAX_RECOMMENDATIONS = 6;
    private const SEARCH_POOL = 40;

    public function __construct(
        private readonly AiPreferenceRepository $aiPreferenceRepository,
        private readonly ListingRepository $listingRepository,
        private readonly ListingService $listingService,
        private readonly DeepSeekChatClient $deepSeekChatClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly AiPreferenceFactory $aiPreferenceFactory,
        private readonly CurrencyConverter $currencyConverter,
    ) {
    }

    public function create(CollectionOwner $owner, CreateAiPreferenceRequest $request): AiPreferenceResponse
    {
        $answers = AiPreferenceAnswers::fromArray($request->answers);
        $filters = $this->buildFilters($answers);
        $searchRequest = $this->toSearchRequest($answers);
        $result = $this->listingRepository->search($searchRequest);
        $ranked = $this->rankListings($result['items'], $answers);
        $recommendedIds = array_slice(
            array_map(static fn ($listing) => (int) $listing->getId(), $ranked),
            0,
            self::MAX_RECOMMENDATIONS,
        );

        $analysis = $this->buildAnalysis($answers, count($recommendedIds));

        $preference = $this->aiPreferenceFactory->create(
            user: $owner->user,
            guestSessionHash: $owner->user === null ? $owner->guestSessionHash : null,
            answers: $answers->toArray(),
            filters: $filters,
            recommendedListingIds: $recommendedIds,
            summary: $analysis['summary'],
            highlights: $analysis['highlights'],
            isTest: false,
        );

        $this->entityManager->persist($preference);
        $this->entityManager->flush();

        return $this->toResponse($preference);
    }

    public function latest(CollectionOwner $owner): ?AiPreferenceResponse
    {
        $preference = $this->aiPreferenceRepository->findLatestByOwner($owner);
        if (!$preference instanceof AiPreference) {
            return null;
        }

        return $this->toResponse($preference);
    }

    public function get(CollectionOwner $owner, int $id): AiPreferenceResponse
    {
        $preference = $this->aiPreferenceRepository->find($id);
        if (!$preference instanceof AiPreference || !$this->owns($owner, $preference)) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_AI_PREFERENCE);
        }

        return $this->toResponse($preference);
    }

    public function softDelete(CollectionOwner $owner, int $id): void
    {
        $preference = $this->aiPreferenceRepository->find($id);
        if (!$preference instanceof AiPreference || !$this->owns($owner, $preference)) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_AI_PREFERENCE);
        }

        $preference->softDelete();
        $this->entityManager->flush();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFilters(AiPreferenceAnswers $answers): array
    {
        $filters = [
            'dealType' => $answers->dealType,
            'minPrice' => $answers->budgetMin,
            'maxPrice' => $answers->budgetMax,
            'rooms' => $answers->rooms,
            'cityId' => $answers->cityId,
        ];

        foreach ($answers->priorities as $priority) {
            if (in_array($priority, ['fromOwner', 'noCommission', 'hasRenovation'], true)) {
                $filters[$priority] = true;
            }
        }

        return array_filter($filters, static fn ($value) => $value !== null);
    }

    private function toSearchRequest(AiPreferenceAnswers $answers): ListingSearchRequest
    {
        $dealType = DealType::tryFrom($answers->dealType);

        $fromOwner = in_array('fromOwner', $answers->priorities, true) ? true : null;
        $noCommission = in_array('noCommission', $answers->priorities, true) ? true : null;
        $hasRenovation = in_array('hasRenovation', $answers->priorities, true) ? true : null;

        return new ListingSearchRequest(
            dealType: $dealType,
            cityId: $answers->cityId,
            rooms: $answers->rooms,
            minPrice: $answers->budgetMin,
            maxPrice: $answers->budgetMax,
            noCommission: $noCommission,
            fromOwner: $fromOwner,
            hasRenovation: $hasRenovation,
            sort: 'publishedAt',
            direction: 'DESC',
            page: 1,
            limit: self::SEARCH_POOL,
        );
    }

    /**
     * @param list<\App\Entity\Listing> $listings
     * @return list<\App\Entity\Listing>
     */
    private function rankListings(array $listings, AiPreferenceAnswers $answers): array
    {
        $wantAiPrice = in_array('aiGoodPrice', $answers->priorities, true);
        $wantMetro = in_array('nearMetro', $answers->priorities, true);

        usort($listings, static function ($a, $b) use ($wantAiPrice, $wantMetro): int {
            $scoreA = 0;
            $scoreB = 0;

            if ($wantAiPrice) {
                $scoreA += $a->isAiGoodPrice() ? 3 : 0;
                $scoreB += $b->isAiGoodPrice() ? 3 : 0;
            }

            if ($wantMetro) {
                $metroA = $a->getMetroMinutes();
                $metroB = $b->getMetroMinutes();
                if ($metroA !== null && $metroA <= 15) {
                    $scoreA += 2;
                }
                if ($metroB !== null && $metroB <= 15) {
                    $scoreB += 2;
                }
            }

            if ($a->isVerified()) {
                $scoreA += 1;
            }
            if ($b->isVerified()) {
                $scoreB += 1;
            }

            return $scoreB <=> $scoreA;
        });

        return $listings;
    }

    /**
     * @return array{summary: string, highlights: list<string>}
     */
    private function buildAnalysis(AiPreferenceAnswers $answers, int $matchCount): array
    {
        $fallback = $this->ruleBasedAnalysis($answers, $matchCount);

        $prompt = sprintf(
            "Ты помощник по недвижимости в Беларуси (Renlo). По ответам опроса сформируй JSON без markdown:\n{\"summary\":\"краткое резюме на русском до 180 символов\",\"highlights\":[\"до 4 коротких пунктов\"]}\nОтветы: %s\nНайдено подходящих объявлений: %d",
            json_encode($answers->toArray(), JSON_UNESCAPED_UNICODE),
            $matchCount,
        );

        try {
            if ($this->deepSeekChatClient->isConfigured()) {
                $raw = $this->deepSeekChatClient->complete([
                    ['role' => 'system', 'content' => 'Отвечай только валидным JSON. Без HTML и без лишнего текста.'],
                    ['role' => 'user', 'content' => $prompt],
                ]);
                $parsed = $this->parseAnalysisJson($raw);
                if ($parsed !== null) {
                    return $parsed;
                }
            }
        } catch (ServiceUnavailableException) {
        } catch (\Throwable) {
        }

        return $fallback;
    }

    /**
     * @return array{summary: string, highlights: list<string>}|null
     */
    private function parseAnalysisJson(string $raw): ?array
    {
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/\{.*\}/s', $trimmed, $matches) === 1) {
            $trimmed = $matches[0];
        }

        try {
            /** @var mixed $data */
            $data = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (!is_array($data)) {
            return null;
        }

        $summary = isset($data['summary']) && is_string($data['summary'])
            ? mb_substr(trim($data['summary']), 0, 300)
            : '';
        if ($summary === '') {
            return null;
        }

        $highlights = [];
        if (isset($data['highlights']) && is_array($data['highlights'])) {
            foreach ($data['highlights'] as $item) {
                if (is_string($item) && trim($item) !== '') {
                    $highlights[] = mb_substr(trim($item), 0, 80);
                }
                if (count($highlights) >= 4) {
                    break;
                }
            }
        }

        return [
            'summary' => $summary,
            'highlights' => $highlights !== [] ? $highlights : ['Подборка по вашим ответам'],
        ];
    }

    /**
     * @return array{summary: string, highlights: list<string>}
     */
    private function ruleBasedAnalysis(AiPreferenceAnswers $answers, int $matchCount): array
    {
        $dealLabel = match ($answers->dealType) {
            'sale' => 'покупку',
            default => 'аренду',
        };

        $parts = [sprintf('Подобрали варианты под %s', $dealLabel)];
        if ($answers->rooms !== null) {
            $parts[] = sprintf('%d комн.', $answers->rooms);
        }
        if ($answers->budgetMin !== null || $answers->budgetMax !== null) {
            $min = $answers->budgetMin ?? 0;
            $max = $answers->budgetMax ?? 0;
            $currencyCode = strtoupper($answers->currency);
            $labelMin = $min > 0 ? $this->formatBudgetLabel($min, $currencyCode) : '';
            $labelMax = $max > 0 ? $this->formatBudgetLabel($max, $currencyCode) : '';
            if ($labelMin !== '' && $labelMax !== '') {
                $parts[] = sprintf('бюджет %s-%s', $labelMin, $labelMax);
            } elseif ($labelMax !== '') {
                $parts[] = sprintf('до %s', $labelMax);
            } elseif ($labelMin !== '') {
                $parts[] = sprintf('от %s', $labelMin);
            }
        }

        $summary = implode(', ', $parts).sprintf('. Найдено: %d.', $matchCount);

        $highlights = [];
        $priorityLabels = [
            'fromOwner' => 'От собственника',
            'noCommission' => 'Без комиссии',
            'hasRenovation' => 'С ремонтом',
            'nearMetro' => 'Рядом с метро',
            'aiGoodPrice' => 'Хорошая цена',
        ];
        foreach ($answers->priorities as $priority) {
            if (isset($priorityLabels[$priority])) {
                $highlights[] = $priorityLabels[$priority];
            }
        }
        if ($highlights === []) {
            $highlights[] = 'По вашим предпочтениям';
        }

        return [
            'summary' => mb_substr($summary, 0, 300),
            'highlights' => array_slice($highlights, 0, 4),
        ];
    }

    private function formatBudgetLabel(int $amountUsd, string $currencyCode): string
    {
        $converted = $this->currencyConverter->fromUsd((string) $amountUsd, $currencyCode);
        $whole = (int) round((float) $converted);

        return match ($currencyCode) {
            'BYN' => sprintf('%d BYN', $whole),
            'RUB' => sprintf('%d RUB', $whole),
            default => sprintf('%d USD', $amountUsd),
        };
    }

    private function toResponse(AiPreference $preference): AiPreferenceResponse
    {
        $listings = [];
        foreach ($preference->getRecommendedListingIds() as $listingId) {
            try {
                $listings[] = $this->listingService->get($listingId);
            } catch (ResourceNotFoundException) {
            }
        }

        return AiPreferenceResponse::fromEntity($preference, $listings);
    }

    private function owns(CollectionOwner $owner, AiPreference $preference): bool
    {
        if ($owner->user instanceof User) {
            return $preference->getUser()?->getId() === $owner->user->getId();
        }

        return $preference->getGuestSessionHash() === $owner->guestSessionHash;
    }
}
