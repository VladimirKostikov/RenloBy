<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add seo_meta table and info_pages SEO fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE seo_meta (id SERIAL NOT NULL, page_key VARCHAR(64) NOT NULL, locale VARCHAR(5) NOT NULL, title VARCHAR(255) NOT NULL, description TEXT NOT NULL, h1 VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_seo_meta_page_locale ON seo_meta (page_key, locale)');
        $this->addSql('ALTER TABLE info_pages ADD meta_title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE info_pages ADD meta_description TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE seo_meta');
        $this->addSql('ALTER TABLE info_pages DROP meta_title');
        $this->addSql('ALTER TABLE info_pages DROP meta_description');
    }
}
