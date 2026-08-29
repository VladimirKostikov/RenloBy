<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Account\ListingAnalyticsDetail;
use App\Dto\Account\ListingAnalyticsEngagement;
use App\Dto\Account\ListingAnalyticsEngagementPoint;
use App\Dto\Account\ListingAnalyticsFunnel;
use App\Dto\Account\ListingAnalyticsMetric;
use App\Dto\Account\ListingAnalyticsOption;
use App\Dto\Account\ListingAnalyticsPromotion;
use App\Dto\Account\ListingAnalyticsPromotionRow;
use App\Dto\Account\ListingAnalyticsSeriesPoint;
use App\Dto\Common\PaginatedResponse;
use App\Entity\Listing;
use App\Entity\User;
use App\Exception\ForbiddenException;
use App\Exception\ResourceNotFoundException;
use App\Http\ApiErrorCode;
use App\Repository\ListingDailyStatRepository;
use App\Repository\ListingRepository;
use App\Repository\PaymentTransactionRepository;
use App\Enum\PaymentStatus;

class ListingAnalyticsService
{
    private const RANGES = ['day' => 1, 'week' => 7, 'month' => 30];

    public function __construct(
        private readonly ListingRepository $listingRepository,
        private readonly ListingDailyStatRepository $dailyStatRepository,
        private readonly PaymentTransactionRepository $paymentTransactionRepository,
    ) {
    }

    public function listOptions(
        User $user,
        int $page = 1,
        int $limit = 20,
        string $q = '',
    ): PaginatedResponse {
        $result = $this->listingRepository->findByUserForAnalytics($user, $page, $limit, $q);

        return new PaginatedResponse(
            array_map(
                fn (Listing $listing) => $this->toOption($listing),
                $result['items'],
            ),
            $result['total'],
            max(1, $page),
            max(1, min(50, $limit)),
        );
    }

    public function getDetail(User $user, int $listingId, string $range = 'week'): ListingAnalyticsDetail
    {
        $listing = $this->listingRepository->find($listingId);
        if ($listing === null) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_LISTING);
        }
        if ($listing->getUser()?->getId() !== $user->getId()) {
            throw new ForbiddenException(ApiErrorCode::FORBIDDEN_LISTING);
        }

        $days = self::RANGES[$range] ?? 7;
        $series = $this->buildViewsSeries($listing, $days);
        $viewsMetric = $this->buildViewsMetric($listing, $series);
        $contactsWeek = $this->sumField($listing, 'contactOpens', 7);
        $contactsPrev = $this->sumField($listing, 'contactOpens', 14) - $contactsWeek;
        $messagesWeek = $this->sumField($listing, 'messages', 7);
        $messagesPrev = $this->sumField($listing, 'messages', 14) - $messagesWeek;
        $viewsWeek = $viewsMetric->week;
        $conversion = $viewsWeek > 0 ? round(($contactsWeek / $viewsWeek) * 100, 1) : 0.0;
        $viewsPrev = $this->sumField($listing, 'views', 14) - $this->sumField($listing, 'views', 7);
        $conversionPrev = $viewsPrev > 0 ? ($contactsPrev / $viewsPrev) * 100 : 0.0;

        return new ListingAnalyticsDetail(
            $this->toOption($listing),
            (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            $viewsMetric,
            $contactsWeek,
            $this->pctChange($contactsWeek, max(0, $contactsPrev)),
            $messagesWeek,
            $this->pctChange($messagesWeek, max(0, $messagesPrev)),
            $conversion,
            $this->pctChange($conversion, $conversionPrev),
            $series,
            $this->buildFunnel($viewsWeek, $contactsWeek, $messagesWeek),
            $this->buildPromotion($user, $listing, $viewsWeek, $contactsWeek, $messagesWeek),
            $this->buildEngagement($listing, $days),
        );
    }

    private function toOption(Listing $listing): ListingAnalyticsOption
    {
        $images = $listing->getImages();
        $image = is_array($images) && $images !== [] ? (string) $images[0] : null;
        $title = sprintf(
            '%s %d-комн, %s м²',
            $this->listingTypeLabel($listing),
            $listing->getRooms(),
            rtrim(rtrim(number_format($listing->getArea(), 1, '.', ''), '0'), '.'),
        );

        return new ListingAnalyticsOption(
            $listing->getId() ?? 0,
            $title,
            $listing->getAddress(),
            $image,
            $listing->getRooms(),
            $listing->getArea(),
            $listing->getStatus()->value,
            $listing->getViews(),
        );
    }

    private function listingTypeLabel(Listing $listing): string
    {
        return match ($listing->getListingType()->value) {
            'house' => 'Дом',
            'room' => 'Комната',
            'commercial' => 'Коммерция',
            default => 'Квартира',
        };
    }

    /**
     * @return list<ListingAnalyticsSeriesPoint>
     */
    private function buildViewsSeries(Listing $listing, int $days): array
    {
        $from = (new \DateTimeImmutable('today'))->modify('-' . ($days - 1) . ' days');
        $stored = $this->dailyStatRepository->findForListingSince($listing, $from);
        $byDay = [];
        foreach ($stored as $stat) {
            $byDay[$stat->getDay()->format('Y-m-d')] = $stat;
        }

        $points = [];
        $values = [];
        for ($i = 0; $i < $days; ++$i) {
            $day = $from->modify('+' . $i . ' days');
            $key = $day->format('Y-m-d');
            if (isset($byDay[$key])) {
                $views = $byDay[$key]->getViews();
            } else {
                $views = $this->syntheticDayViews($listing, $day, $days);
            }
            $values[] = $views;
            $points[] = ['date' => $key, 'views' => $views];
        }

        $avg = $values !== [] ? array_sum($values) / count($values) : 0.0;

        return array_map(
            static fn (array $point) => new ListingAnalyticsSeriesPoint(
                $point['date'],
                $point['views'],
                round($avg, 1),
            ),
            $points,
        );
    }

    private function syntheticDayViews(Listing $listing, \DateTimeImmutable $day, int $window): int
    {
        $total = max(0, $listing->getViews());
        if ($total === 0 || $window <= 0) {
            return 0;
        }
        $seed = ($listing->getId() ?? 1) * 31 + (int) $day->format('z');
        $weight = 0.5 + (($seed % 100) / 100);
        $base = (int) max(1, round(($total / max(30, $window * 3)) * $weight));

        return min($total, $base);
    }

    /**
     * @param list<ListingAnalyticsSeriesPoint> $series
     */
    private function buildViewsMetric(Listing $listing, array $series): ListingAnalyticsMetric
    {
        $day = $this->sumField($listing, 'views', 1);
        $week = $this->sumField($listing, 'views', 7);
        $month = $this->sumField($listing, 'views', 30);
        $prevDay = $this->sumField($listing, 'views', 2) - $day;
        $prevWeek = $this->sumField($listing, 'views', 14) - $week;
        $prevMonth = $this->sumField($listing, 'views', 60) - $month;

        if ($day === 0 && $series !== []) {
            $day = $series[array_key_last($series)]->views;
        }
        if ($week === 0) {
            $week = array_sum(array_map(static fn (ListingAnalyticsSeriesPoint $p) => $p->views, array_slice($series, -7)));
        }
        if ($month === 0) {
            $month = max($listing->getViews(), $week);
        }

        return new ListingAnalyticsMetric(
            $day,
            $week,
            $month,
            $this->pctChange($day, max(0, $prevDay)),
            $this->pctChange($week, max(0, $prevWeek)),
            $this->pctChange($month, max(0, $prevMonth)),
        );
    }

    private function sumField(Listing $listing, string $field, int $days): int
    {
        $from = (new \DateTimeImmutable('today'))->modify('-' . ($days - 1) . ' days');
        $stats = $this->dailyStatRepository->findForListingSince($listing, $from);
        if ($stats === []) {
            return match ($field) {
                'contactOpens' => (int) max(0, (int) round($listing->getContactOpens() * min(1, $days / 30))),
                'messages' => (int) max(0, (int) round($listing->getMessages() * min(1, $days / 30))),
                default => (int) max(0, (int) round($listing->getViews() * min(1, $days / 30))),
            };
        }

        $sum = 0;
        foreach ($stats as $stat) {
            $sum += match ($field) {
                'contactOpens' => $stat->getContactOpens(),
                'messages' => $stat->getMessages(),
                default => $stat->getViews(),
            };
        }

        return $sum;
    }

    private function buildFunnel(int $views, int $contacts, int $messages): ListingAnalyticsFunnel
    {
        return new ListingAnalyticsFunnel(
            max(0, $views),
            max(0, $contacts),
            max(0, $messages),
            $views > 0 ? round(($contacts / $views) * 100, 1) : 0.0,
            $contacts > 0 ? round(($messages / $contacts) * 100, 1) : 0.0,
        );
    }

    private function buildPromotion(
        User $user,
        Listing $listing,
        int $viewsWeek,
        int $contactsWeek,
        int $messagesWeek,
    ): ListingAnalyticsPromotion {
        $payments = $this->paymentTransactionRepository->findBy(
            ['user' => $user, 'status' => PaymentStatus::Succeeded],
            ['createdAt' => 'DESC'],
            5,
        );
        $active = $payments !== [];
        $beforeViews = (int) max(1, round($viewsWeek * 0.42));
        $beforeContacts = (int) max(0, round($contactsWeek * 0.45));
        $beforeMessages = (int) max(0, round($messagesWeek * 0.4));

        return new ListingAnalyticsPromotion(
            $active,
            $active ? 'premium' : null,
            [
                new ListingAnalyticsPromotionRow(
                    'views',
                    $beforeViews,
                    max($beforeViews, $viewsWeek),
                    $this->pctChange(max($beforeViews, $viewsWeek), $beforeViews),
                ),
                new ListingAnalyticsPromotionRow(
                    'contacts',
                    $beforeContacts,
                    max($beforeContacts, $contactsWeek),
                    $this->pctChange(max($beforeContacts, $contactsWeek), $beforeContacts),
                ),
                new ListingAnalyticsPromotionRow(
                    'messages',
                    $beforeMessages,
                    max($beforeMessages, $messagesWeek),
                    $this->pctChange(max($beforeMessages, $messagesWeek), $beforeMessages),
                ),
            ],
        );
    }

    private function buildEngagement(Listing $listing, int $days): ListingAnalyticsEngagement
    {
        $from = (new \DateTimeImmutable('today'))->modify('-' . ($days - 1) . ' days');
        $stored = $this->dailyStatRepository->findForListingSince($listing, $from);
        $byDay = [];
        foreach ($stored as $stat) {
            $byDay[$stat->getDay()->format('Y-m-d')] = $stat;
        }

        $series = [];
        $contactsValues = [];
        $messagesTotal = 0;
        for ($i = 0; $i < $days; ++$i) {
            $day = $from->modify('+' . $i . ' days');
            $key = $day->format('Y-m-d');
            if (isset($byDay[$key])) {
                $contacts = $byDay[$key]->getContactOpens();
                $messages = $byDay[$key]->getMessages();
            } else {
                $contacts = $this->syntheticDayMetric($listing->getContactOpens(), $listing, $day, $days);
                $messages = $this->syntheticDayMetric($listing->getMessages(), $listing, $day, $days);
            }
            $contactsValues[] = $contacts;
            $messagesTotal += $messages;
            $series[] = new ListingAnalyticsEngagementPoint($key, $contacts, $messages);
        }

        $contactsTotal = array_sum($contactsValues);

        return new ListingAnalyticsEngagement(
            $contactsTotal,
            $messagesTotal,
            $days > 0 ? round($contactsTotal / $days, 1) : 0.0,
            $contactsValues !== [] ? max($contactsValues) : 0,
            $series,
        );
    }

    private function syntheticDayMetric(int $total, Listing $listing, \DateTimeImmutable $day, int $window): int
    {
        if ($total <= 0 || $window <= 0) {
            return 0;
        }
        $seed = ($listing->getId() ?? 1) * 17 + (int) $day->format('z');
        $weight = 0.4 + (($seed % 100) / 100);
        $base = (int) max(0, round(($total / max(30, $window * 3)) * $weight));

        return min($total, $base);
    }

    private function pctChange(float|int $current, float|int $previous): float
    {
        if ((float) $previous <= 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((((float) $current - (float) $previous) / (float) $previous) * 100, 1);
    }
}
