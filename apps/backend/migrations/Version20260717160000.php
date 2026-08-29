<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add head_snippets table for custom HTML in document head';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE head_snippets (
            id SERIAL NOT NULL,
            name VARCHAR(255) NOT NULL,
            code TEXT NOT NULL,
            is_enabled BOOLEAN DEFAULT true NOT NULL,
            sort_order INT DEFAULT 0 NOT NULL,
            is_test BOOLEAN DEFAULT false NOT NULL,
            deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_head_snippets_is_test ON head_snippets (is_test)');
        $this->addSql('CREATE INDEX idx_head_snippets_enabled ON head_snippets (is_enabled)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE head_snippets');
    }
}
