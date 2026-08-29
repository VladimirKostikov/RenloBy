<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Split user full name into lastName, firstName, patronymic';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD last_name VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD first_name VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD patronymic VARCHAR(80) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP patronymic');
        $this->addSql('ALTER TABLE users DROP first_name');
        $this->addSql('ALTER TABLE users DROP last_name');
    }
}
