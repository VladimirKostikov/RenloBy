<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260829191000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add listing price_negotiable, contact_opens, messages columns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE listings ADD COLUMN IF NOT EXISTS price_negotiable BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE listings ADD COLUMN IF NOT EXISTS contact_opens INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE listings ADD COLUMN IF NOT EXISTS messages INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE listings DROP COLUMN IF EXISTS price_negotiable');
        $this->addSql('ALTER TABLE listings DROP COLUMN IF EXISTS contact_opens');
        $this->addSql('ALTER TABLE listings DROP COLUMN IF EXISTS messages');
    }
}
