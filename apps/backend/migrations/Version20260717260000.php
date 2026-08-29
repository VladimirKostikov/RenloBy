<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717260000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_telegram_links for seller bot notifications';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_telegram_links (
            id SERIAL NOT NULL,
            user_id INT NOT NULL,
            chat_id VARCHAR(64) NOT NULL,
            username VARCHAR(255) DEFAULT NULL,
            first_name VARCHAR(255) DEFAULT NULL,
            is_active BOOLEAN DEFAULT true NOT NULL,
            connected_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_TELEGRAM_LINKS_USER ON user_telegram_links (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_TELEGRAM_LINKS_CHAT ON user_telegram_links (chat_id)');
        $this->addSql('ALTER TABLE user_telegram_links ADD CONSTRAINT FK_USER_TELEGRAM_LINKS_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_telegram_links DROP CONSTRAINT FK_USER_TELEGRAM_LINKS_USER');
        $this->addSql('DROP TABLE user_telegram_links');
    }
}
