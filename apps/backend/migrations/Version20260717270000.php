<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717270000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add last_seen_at to users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD last_seen_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP last_seen_at');
    }
}
