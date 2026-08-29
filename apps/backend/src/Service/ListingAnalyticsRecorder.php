<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Listing;
use App\Entity\ListingDailyStat;
use App\Repository\ListingDailyStatRepository;
use Doctrine\ORM\EntityManagerInterface;

class ListingAnalyticsRecorder
{
    public function __construct(
        private readonly ListingDailyStatRepository $dailyStatRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function recordView(Listing $listing): void
    {
        $listing->setViews($listing->getViews() + 1);
        $stat = $this->todayStat($listing);
        $stat->setViews($stat->getViews() + 1);
        $this->entityManager->flush();
    }

    public function recordContactOpen(Listing $listing): void
    {
        $listing->setContactOpens($listing->getContactOpens() + 1);
        $stat = $this->todayStat($listing);
        $stat->setContactOpens($stat->getContactOpens() + 1);
        $this->entityManager->flush();
    }

    public function recordMessage(Listing $listing): void
    {
        $listing->setMessages($listing->getMessages() + 1);
        $stat = $this->todayStat($listing);
        $stat->setMessages($stat->getMessages() + 1);
        $this->entityManager->flush();
    }

    private function todayStat(Listing $listing): ListingDailyStat
    {
        $day = new \DateTimeImmutable('today');
        $stat = $this->dailyStatRepository->findOneByListingAndDay($listing, $day);
        if ($stat !== null) {
            return $stat;
        }

        $stat = (new ListingDailyStat())
            ->setListing($listing)
            ->setDay($day);
        $this->entityManager->persist($stat);

        return $stat;
    }
}
