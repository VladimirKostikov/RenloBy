<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717310000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add registered_at to users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD registered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('UPDATE users SET registered_at = COALESCE(last_seen_at, CURRENT_TIMESTAMP) WHERE registered_at IS NULL');
        $this->addSql('ALTER TABLE users ALTER registered_at SET NOT NULL');
        $this->addSql('COMMENT ON COLUMN users.registered_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP registered_at');
    }
}
