<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add rent filter fields to listings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE listings ADD rent_term VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE listings ADD has_deposit BOOLEAN NOT NULL DEFAULT false');
        $this->addSql('ALTER TABLE listings ADD utilities_included BOOLEAN NOT NULL DEFAULT false');
        $this->addSql('ALTER TABLE listings ADD no_commission BOOLEAN NOT NULL DEFAULT false');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE listings DROP rent_term');
        $this->addSql('ALTER TABLE listings DROP has_deposit');
        $this->addSql('ALTER TABLE listings DROP utilities_included');
        $this->addSql('ALTER TABLE listings DROP no_commission');
    }
}
