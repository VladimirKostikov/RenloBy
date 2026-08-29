<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260829190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create listing_daily_stats table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE listing_daily_stats (
            id SERIAL NOT NULL,
            listing_id INT NOT NULL,
            day DATE NOT NULL,
            views INT NOT NULL,
            contact_opens INT NOT NULL,
            messages INT NOT NULL,
            PRIMARY KEY (id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_listing_daily_stats_listing_day ON listing_daily_stats (listing_id, day)');
        $this->addSql('CREATE INDEX IDX_LISTING_DAILY_STATS_LISTING ON listing_daily_stats (listing_id)');
        $this->addSql('ALTER TABLE listing_daily_stats ADD CONSTRAINT FK_LISTING_DAILY_STATS_LISTING FOREIGN KEY (listing_id) REFERENCES listings (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE listing_daily_stats DROP CONSTRAINT FK_LISTING_DAILY_STATS_LISTING');
        $this->addSql('DROP TABLE listing_daily_stats');
    }
}
