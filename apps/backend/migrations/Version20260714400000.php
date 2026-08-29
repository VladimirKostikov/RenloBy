<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714400000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add guest session support for favorites and comparisons';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE favorites ALTER COLUMN user_id DROP NOT NULL');
        $this->addSql('ALTER TABLE favorites ADD guest_session_hash VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_E46960F5_GUEST_SESSION ON favorites (guest_session_hash)');
        $this->addSql('CREATE UNIQUE INDEX favorites_guest_listing_unique ON favorites (guest_session_hash, listing_id) WHERE guest_session_hash IS NOT NULL');

        $this->addSql('ALTER TABLE comparisons ALTER COLUMN user_id DROP NOT NULL');
        $this->addSql('ALTER TABLE comparisons ADD guest_session_hash VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_AAAECBCD_GUEST_SESSION ON comparisons (guest_session_hash)');
        $this->addSql('CREATE UNIQUE INDEX comparisons_guest_listing_unique ON comparisons (guest_session_hash, listing_id) WHERE guest_session_hash IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX comparisons_guest_listing_unique');
        $this->addSql('DROP INDEX IDX_AAAECBCD_GUEST_SESSION');
        $this->addSql('ALTER TABLE comparisons DROP guest_session_hash');
        $this->addSql('ALTER TABLE comparisons ALTER COLUMN user_id SET NOT NULL');

        $this->addSql('DROP INDEX favorites_guest_listing_unique');
        $this->addSql('DROP INDEX IDX_E46960F5_GUEST_SESSION');
        $this->addSql('ALTER TABLE favorites DROP guest_session_hash');
        $this->addSql('ALTER TABLE favorites ALTER COLUMN user_id SET NOT NULL');
    }
}
