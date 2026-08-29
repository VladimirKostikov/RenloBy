<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ListingCreatedMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ListingCreatedMessageHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ListingCreatedMessage $message): void
    {
        $this->logger->info('Listing created', [
            'listingId' => $message->listingId,
            'transport' => 'kafka',
        ]);
    }
}
