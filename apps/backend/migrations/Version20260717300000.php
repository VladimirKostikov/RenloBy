<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717300000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow null floor and total_floors on listings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE listings ALTER floor DROP NOT NULL');
        $this->addSql('ALTER TABLE listings ALTER total_floors DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE listings SET floor = 1 WHERE floor IS NULL');
        $this->addSql('UPDATE listings SET total_floors = 1 WHERE total_floors IS NULL');
        $this->addSql('ALTER TABLE listings ALTER floor SET NOT NULL');
        $this->addSql('ALTER TABLE listings ALTER total_floors SET NOT NULL');
    }
}
