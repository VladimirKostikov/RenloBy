<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add social media URLs to site_settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_settings ADD telegram_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE site_settings ADD whatsapp_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE site_settings ADD vk_url VARCHAR(255) DEFAULT NULL');
        $this->addSql("UPDATE site_settings SET telegram_url = 'https://t.me/renlo_bot', whatsapp_url = 'https://wa.me/375290000000', vk_url = 'https://vk.com/renlo' WHERE telegram_url IS NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_settings DROP telegram_url');
        $this->addSql('ALTER TABLE site_settings DROP whatsapp_url');
        $this->addSql('ALTER TABLE site_settings DROP vk_url');
    }
}
