<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\AdminNationwideListingSeeder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:seed-admin-nationwide-listings',
    description: 'Seed 50 published listings for admin across Belarus cities',
)]
final class SeedAdminNationwideListingsCommand extends Command
{
    public function __construct(
        private readonly AdminNationwideListingSeeder $seeder,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'force',
            null,
            InputOption::VALUE_NONE,
            'Create even if matching admin address already exists',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $skipExisting = !$input->getOption('force');
        $result = $this->seeder->seed($skipExisting);

        $output->writeln(sprintf(
            'Admin nationwide listings: created %d, skipped %d',
            $result['created'],
            $result['skipped'],
        ));

        return Command::SUCCESS;
    }
}
