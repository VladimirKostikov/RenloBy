<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\MetroStationDirectorySeeder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:seed-metro-stations',
    description: 'Sync full Minsk metro station directory',
)]
final class SeedMetroStationsCommand extends Command
{
    public function __construct(
        private readonly MetroStationDirectorySeeder $seeder,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->seeder->syncMinsk();

        $output->writeln(sprintf(
            'Minsk metro stations: created %d, updated %d',
            $result['created'],
            $result['updated'],
        ));

        return Command::SUCCESS;
    }
}
