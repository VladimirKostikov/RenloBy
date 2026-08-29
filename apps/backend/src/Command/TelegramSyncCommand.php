<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\TelegramNotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:telegram:sync',
    description: 'Pull pending Telegram bot updates and connect subscribers (for local without webhook)',
)]
final class TelegramSyncCommand extends Command
{
    public function __construct(
        private readonly TelegramNotificationService $telegramNotificationService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->telegramNotificationService->isConfigured()) {
            $io->error('TELEGRAM_BOT_TOKEN is not configured');

            return Command::FAILURE;
        }

        $result = $this->telegramNotificationService->syncPendingUpdates();
        $io->success(sprintf(
            'Processed %d update(s), connected %d chat(s)',
            $result['processed'],
            $result['connected'],
        ));

        return Command::SUCCESS;
    }
}
