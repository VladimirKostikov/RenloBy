<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add media JSON column to articles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE articles ADD media JSON DEFAULT \'[]\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE articles DROP media');
    }
}
