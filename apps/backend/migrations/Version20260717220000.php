<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_notifications table for listing status alerts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_notifications (
            id SERIAL NOT NULL,
            user_id INT NOT NULL,
            type VARCHAR(40) NOT NULL,
            payload JSON NOT NULL,
            read_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            is_test BOOLEAN DEFAULT false NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_user_notifications_user_read ON user_notifications (user_id, read_at)');
        $this->addSql('ALTER TABLE user_notifications ADD CONSTRAINT FK_USER_NOTIFICATIONS_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('COMMENT ON COLUMN user_notifications.read_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN user_notifications.created_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_notifications DROP CONSTRAINT FK_USER_NOTIFICATIONS_USER');
        $this->addSql('DROP TABLE user_notifications');
    }
}
