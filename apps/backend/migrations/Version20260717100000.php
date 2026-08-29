<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add listing_reports and telegram_subscribers tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE listing_reports (
            id SERIAL NOT NULL,
            listing_id INT NOT NULL,
            reason VARCHAR(20) NOT NULL,
            comment TEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL,
            is_test BOOLEAN DEFAULT false NOT NULL,
            deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_LISTING_REPORTS_LISTING ON listing_reports (listing_id)');
        $this->addSql('CREATE INDEX IDX_LISTING_REPORTS_STATUS ON listing_reports (status)');
        $this->addSql('ALTER TABLE listing_reports ADD CONSTRAINT FK_LISTING_REPORTS_LISTING FOREIGN KEY (listing_id) REFERENCES listings (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE telegram_subscribers (
            id SERIAL NOT NULL,
            chat_id VARCHAR(64) NOT NULL,
            username VARCHAR(255) DEFAULT NULL,
            first_name VARCHAR(255) DEFAULT NULL,
            is_active BOOLEAN DEFAULT true NOT NULL,
            connected_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_TELEGRAM_SUBSCRIBERS_CHAT ON telegram_subscribers (chat_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE listing_reports DROP CONSTRAINT FK_LISTING_REPORTS_LISTING');
        $this->addSql('DROP TABLE listing_reports');
        $this->addSql('DROP TABLE telegram_subscribers');
    }
}
