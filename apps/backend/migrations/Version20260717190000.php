<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create media_files table for uploaded media registry';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE media_files (
            id SERIAL NOT NULL,
            uploaded_by_id INT DEFAULT NULL,
            url VARCHAR(500) NOT NULL,
            type VARCHAR(16) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            size INT NOT NULL,
            context VARCHAR(32) NOT NULL,
            original_name VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            is_test BOOLEAN DEFAULT false NOT NULL,
            deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_media_files_url ON media_files (url)');
        $this->addSql('CREATE INDEX idx_media_files_context ON media_files (context)');
        $this->addSql('CREATE INDEX idx_media_files_is_test ON media_files (is_test)');
        $this->addSql('ALTER TABLE media_files ADD CONSTRAINT FK_MEDIA_FILES_UPLOADED_BY FOREIGN KEY (uploaded_by_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE media_files DROP CONSTRAINT FK_MEDIA_FILES_UPLOADED_BY');
        $this->addSql('DROP TABLE media_files');
    }
}
