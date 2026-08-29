<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add keywords to seo_meta and unique by page/locale/is_test';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE seo_meta ADD keywords VARCHAR(512) DEFAULT NULL');
        $this->addSql('DROP INDEX uniq_seo_meta_page_locale');
        $this->addSql('CREATE UNIQUE INDEX uniq_seo_meta_page_locale_is_test ON seo_meta (page_key, locale, is_test)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_seo_meta_page_locale_is_test');
        $this->addSql('CREATE UNIQUE INDEX uniq_seo_meta_page_locale ON seo_meta (page_key, locale)');
        $this->addSql('ALTER TABLE seo_meta DROP keywords');
    }
}
