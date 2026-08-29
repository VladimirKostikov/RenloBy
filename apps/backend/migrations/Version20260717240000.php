<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717240000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow articles slug unique per is_test for public and test copies';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_articles_slug');
        $this->addSql('DROP INDEX IF EXISTS uniq_bfdd3168989d9b62');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_articles_slug_is_test ON articles (slug, is_test)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_articles_slug_is_test');
        $this->addSql('CREATE UNIQUE INDEX uniq_articles_slug ON articles (slug)');
    }
}
