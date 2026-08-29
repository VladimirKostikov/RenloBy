<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tariffs table for promotion plan prices';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE tariffs (
            id SERIAL NOT NULL,
            code VARCHAR(32) NOT NULL,
            price_usd NUMERIC(12, 2) NOT NULL,
            currency VARCHAR(3) DEFAULT \'USD\' NOT NULL,
            is_popular BOOLEAN DEFAULT false NOT NULL,
            sort_order INT DEFAULT 0 NOT NULL,
            is_test BOOLEAN DEFAULT false NOT NULL,
            deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_tariffs_code_is_test ON tariffs (code, is_test)');
        $this->addSql('CREATE INDEX idx_tariffs_is_test ON tariffs (is_test)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tariffs');
    }
}
