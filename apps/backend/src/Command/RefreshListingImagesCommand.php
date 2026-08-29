<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\ListingRepository;
use App\Service\ListingImageCatalog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:listings:refresh-images', description: 'Refresh listing images with stock photos')]
final class RefreshListingImagesCommand extends Command
{
    public function __construct(
        private readonly ListingRepository $listingRepository,
        private readonly ListingImageCatalog $imageCatalog,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $listings = $this->listingRepository->findAll();

        foreach ($listings as $index => $listing) {
            $listing->setImages($this->imageCatalog->forIndex($index));
            $listing->setLatitude(53.9045 + sin($index * 0.62) * 0.035);
            $listing->setLongitude(27.5615 + cos($index * 0.62) * 0.055);
        }

        $this->entityManager->flush();
        $output->writeln(sprintf('Updated %d listings', count($listings)));

        return Command::SUCCESS;
    }
}
