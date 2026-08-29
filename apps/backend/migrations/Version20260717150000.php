<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow info_pages slug unique per is_test for public and test copies';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_info_pages_slug');
        $this->addSql('CREATE UNIQUE INDEX uniq_info_pages_slug_is_test ON info_pages (slug, is_test)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_info_pages_slug_is_test');
        $this->addSql('CREATE UNIQUE INDEX uniq_info_pages_slug ON info_pages (slug)');
    }
}
