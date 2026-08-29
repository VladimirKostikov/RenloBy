<?php

declare(strict_types=1);

namespace App\Command;

use App\Enum\ListingStatus;
use App\Repository\ListingRepository;
use App\Service\ListingGoodPriceEvaluator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:listings:recompute-ai-good-price',
    description: 'Recompute AI good price flags from Belarus market comps',
)]
final class RecomputeAiGoodPriceCommand extends Command
{
    public function __construct(
        private readonly ListingRepository $listingRepository,
        private readonly ListingGoodPriceEvaluator $listingGoodPriceEvaluator,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Max listings to process', '0');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = max(0, (int) $input->getOption('limit'));
        $listings = $this->listingRepository->findBy(
            ['status' => ListingStatus::Published],
            ['id' => 'ASC'],
            $limit > 0 ? $limit : null,
        );

        $marked = 0;
        foreach ($listings as $index => $listing) {
            $verdict = $this->listingGoodPriceEvaluator->apply($listing);
            if ($verdict->isGoodPrice) {
                ++$marked;
            }
            if (($index + 1) % 50 === 0) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();
        $output->writeln(sprintf(
            'Processed %d listings, marked AI good price: %d',
            count($listings),
            $marked,
        ));

        return Command::SUCCESS;
    }
}
