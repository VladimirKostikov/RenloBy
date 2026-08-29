<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714300000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add sale filter fields to listings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE listings ADD from_owner BOOLEAN NOT NULL DEFAULT false');
        $this->addSql('ALTER TABLE listings ADD has_renovation BOOLEAN NOT NULL DEFAULT false');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE listings DROP from_owner');
        $this->addSql('ALTER TABLE listings DROP has_renovation');
    }
}
