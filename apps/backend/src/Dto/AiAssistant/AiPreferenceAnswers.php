<?php

declare(strict_types=1);

namespace App\Dto\AiAssistant;

/**
 * Validated questionnaire answers for AI matching.
 */
readonly class AiPreferenceAnswers
{
    public const ALLOWED_PRIORITIES = [
        'fromOwner',
        'noCommission',
        'hasRenovation',
        'nearMetro',
        'aiGoodPrice',
    ];

    public const ALLOWED_CURRENCIES = ['byn', 'usd'];

    /**
     * @param list<string> $priorities
     */
    public function __construct(
        public string $dealType,
        public string $currency,
        public ?int $budgetMin,
        public ?int $budgetMax,
        public ?int $rooms,
        public ?int $cityId,
        public array $priorities,
    ) {
    }

    /**
     * @param array<string, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        $dealType = isset($raw['dealType']) && is_string($raw['dealType']) ? $raw['dealType'] : 'rent';
        if (!in_array($dealType, ['rent', 'sale'], true)) {
            $dealType = 'rent';
        }

        $currency = isset($raw['currency']) && is_string($raw['currency'])
            ? strtolower(trim($raw['currency']))
            : 'byn';
        if (!in_array($currency, self::ALLOWED_CURRENCIES, true)) {
            $currency = 'byn';
        }

        $budgetMin = self::optionalPositiveInt($raw['budgetMin'] ?? null);
        $budgetMax = self::optionalPositiveInt($raw['budgetMax'] ?? null);
        if ($budgetMin !== null && $budgetMax !== null && $budgetMin > $budgetMax) {
            [$budgetMin, $budgetMax] = [$budgetMax, $budgetMin];
        }

        $rooms = self::optionalRooms($raw['rooms'] ?? null);

        $cityId = self::optionalPositiveInt($raw['cityId'] ?? null);

        $priorities = [];
        if (isset($raw['priorities']) && is_array($raw['priorities'])) {
            foreach ($raw['priorities'] as $item) {
                if (is_string($item) && in_array($item, self::ALLOWED_PRIORITIES, true)) {
                    $priorities[] = $item;
                }
            }
        }
        $priorities = array_values(array_unique($priorities));

        return new self($dealType, $currency, $budgetMin, $budgetMax, $rooms, $cityId, $priorities);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'dealType' => $this->dealType,
            'currency' => $this->currency,
            'budgetMin' => $this->budgetMin,
            'budgetMax' => $this->budgetMax,
            'rooms' => $this->rooms,
            'cityId' => $this->cityId,
            'priorities' => $this->priorities,
        ];
    }

    private static function optionalRooms(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            return null;
        }
        $rooms = (int) $value;

        return $rooms >= 0 && $rooms <= 10 ? $rooms : null;
    }

    private static function optionalPositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            return null;
        }
        $int = (int) $value;

        return $int > 0 ? $int : null;
    }
}
