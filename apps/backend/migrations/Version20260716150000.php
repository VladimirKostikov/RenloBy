<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add listing status for seller drafts and archive';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE listings ADD status VARCHAR(20) NOT NULL DEFAULT 'published'");
        $this->addSql("CREATE INDEX IDX_listings_status ON listings (status)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_listings_status');
        $this->addSql('ALTER TABLE listings DROP status');
    }
}
