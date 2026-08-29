<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ai_preferences table for AI assistant questionnaire';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ai_preferences (
            id SERIAL NOT NULL,
            user_id INT DEFAULT NULL,
            guest_session_hash VARCHAR(64) DEFAULT NULL,
            answers JSON NOT NULL,
            filters JSON NOT NULL,
            recommended_listing_ids JSON NOT NULL,
            summary TEXT DEFAULT NULL,
            highlights JSON NOT NULL,
            is_test BOOLEAN DEFAULT false NOT NULL,
            deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_ai_preferences_user ON ai_preferences (user_id)');
        $this->addSql('CREATE INDEX idx_ai_preferences_guest ON ai_preferences (guest_session_hash)');
        $this->addSql('ALTER TABLE ai_preferences ADD CONSTRAINT FK_AI_PREFERENCES_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('COMMENT ON COLUMN ai_preferences.deleted_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN ai_preferences.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN ai_preferences.updated_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ai_preferences DROP CONSTRAINT FK_AI_PREFERENCES_USER');
        $this->addSql('DROP TABLE ai_preferences');
    }
}
