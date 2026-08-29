<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717280000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store tariff prices in USD, BYN and RUB';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tariffs ADD price_byn NUMERIC(12, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE tariffs ADD price_rub NUMERIC(12, 2) DEFAULT NULL');
        $this->addSql("UPDATE tariffs SET price_byn = ROUND((price_usd::numeric * 3.27), 2) WHERE price_byn IS NULL");
        $this->addSql("UPDATE tariffs SET price_rub = (ROUND((price_usd::numeric * 93) / 10) * 10) WHERE price_rub IS NULL");
        $this->addSql('ALTER TABLE tariffs ALTER COLUMN price_byn SET NOT NULL');
        $this->addSql('ALTER TABLE tariffs ALTER COLUMN price_rub SET NOT NULL');
        $this->addSql('ALTER TABLE tariffs ALTER COLUMN price_byn DROP DEFAULT');
        $this->addSql('ALTER TABLE tariffs ALTER COLUMN price_rub DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tariffs DROP COLUMN price_byn');
        $this->addSql('ALTER TABLE tariffs DROP COLUMN price_rub');
    }
}
