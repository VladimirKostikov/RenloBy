<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717330000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make listings.district_id nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE listings ALTER district_id DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE listings ALTER district_id SET NOT NULL');
    }
}
