<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add site_settings table for company contacts and about info';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE site_settings (
            id SERIAL NOT NULL,
            about_text TEXT NOT NULL,
            phone_display VARCHAR(64) NOT NULL,
            phone_raw VARCHAR(64) NOT NULL,
            email VARCHAR(255) NOT NULL,
            support_hours VARCHAR(255) NOT NULL,
            owner_name VARCHAR(255) DEFAULT NULL,
            address VARCHAR(255) DEFAULT NULL,
            offers_text TEXT DEFAULT NULL,
            offers_email VARCHAR(255) DEFAULT NULL,
            is_test BOOLEAN DEFAULT false NOT NULL,
            deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_site_settings_is_test ON site_settings (is_test)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE site_settings');
    }
}
