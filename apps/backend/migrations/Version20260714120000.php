<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create info_pages table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE info_pages (id SERIAL NOT NULL, slug VARCHAR(120) NOT NULL, title VARCHAR(255) NOT NULL, body TEXT NOT NULL, category VARCHAR(30) NOT NULL, important_note TEXT DEFAULT NULL, faq_items JSON NOT NULL, sort_order INT NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_INFO_PAGES_SLUG ON info_pages (slug)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE info_pages');
    }
}
