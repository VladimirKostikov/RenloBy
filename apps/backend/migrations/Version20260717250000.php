<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717250000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add listing_requests table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE listing_requests (
            id SERIAL NOT NULL,
            listing_id INT NOT NULL,
            requester_id INT DEFAULT NULL,
            name VARCHAR(120) DEFAULT NULL,
            phone VARCHAR(32) NOT NULL,
            message TEXT NOT NULL,
            status VARCHAR(20) NOT NULL,
            is_test BOOLEAN DEFAULT false NOT NULL,
            deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_LISTING_REQUESTS_LISTING ON listing_requests (listing_id)');
        $this->addSql('CREATE INDEX IDX_LISTING_REQUESTS_REQUESTER ON listing_requests (requester_id)');
        $this->addSql('CREATE INDEX IDX_LISTING_REQUESTS_STATUS ON listing_requests (status)');
        $this->addSql('ALTER TABLE listing_requests ADD CONSTRAINT FK_LISTING_REQUESTS_LISTING FOREIGN KEY (listing_id) REFERENCES listings (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE listing_requests ADD CONSTRAINT FK_LISTING_REQUESTS_REQUESTER FOREIGN KEY (requester_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE listing_requests DROP CONSTRAINT FK_LISTING_REQUESTS_LISTING');
        $this->addSql('ALTER TABLE listing_requests DROP CONSTRAINT FK_LISTING_REQUESTS_REQUESTER');
        $this->addSql('DROP TABLE listing_requests');
    }
}
