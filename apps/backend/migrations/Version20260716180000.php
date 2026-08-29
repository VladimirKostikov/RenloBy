<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create articles table for SEO content section';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE articles (
            id SERIAL NOT NULL,
            slug VARCHAR(120) NOT NULL,
            title VARCHAR(255) NOT NULL,
            excerpt TEXT NOT NULL,
            body TEXT NOT NULL,
            category VARCHAR(30) NOT NULL,
            cover_image VARCHAR(500) DEFAULT NULL,
            is_published BOOLEAN NOT NULL,
            published_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            meta_title VARCHAR(255) DEFAULT NULL,
            meta_description TEXT DEFAULT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_articles_slug ON articles (slug)');
        $this->addSql('CREATE INDEX idx_articles_published ON articles (is_published, published_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE articles');
    }
}
