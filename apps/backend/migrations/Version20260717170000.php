<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user profile photo, phone and social links';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ALTER name TYPE VARCHAR(150)');
        $this->addSql('ALTER TABLE users ADD photo VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD phone VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD instagram VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD telegram VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD whatsapp VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD viber VARCHAR(120) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP photo');
        $this->addSql('ALTER TABLE users DROP phone');
        $this->addSql('ALTER TABLE users DROP instagram');
        $this->addSql('ALTER TABLE users DROP telegram');
        $this->addSql('ALTER TABLE users DROP whatsapp');
        $this->addSql('ALTER TABLE users DROP viber');
        $this->addSql('ALTER TABLE users ALTER name TYPE VARCHAR(100)');
    }
}
