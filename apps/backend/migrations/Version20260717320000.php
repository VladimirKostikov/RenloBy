<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717320000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional SEO meta fields to listings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE listings ADD meta_title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE listings ADD meta_description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE listings ADD meta_keywords VARCHAR(512) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE listings DROP meta_keywords');
        $this->addSql('ALTER TABLE listings DROP meta_description');
        $this->addSql('ALTER TABLE listings DROP meta_title');
    }
}
