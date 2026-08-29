<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add region_slug to cities for oblast filters';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE cities ADD region_slug VARCHAR(50) NOT NULL DEFAULT ''");
        $this->addSql("UPDATE cities SET region_slug = 'minsk-city' WHERE slug = 'minsk'");
        $this->addSql("UPDATE cities SET region_slug = 'minsk-region' WHERE slug IN ('borisov', 'soligorsk', 'molodechno', 'zhodino', 'berezino')");
        $this->addSql("UPDATE cities SET region_slug = 'brest' WHERE slug IN ('brest-city', 'motol')");
        $this->addSql("UPDATE cities SET region_slug = 'vitebsk' WHERE slug = 'vitebsk-city'");
        $this->addSql("UPDATE cities SET region_slug = 'gomel' WHERE slug IN ('gomel-city', 'chechersk')");
        $this->addSql("UPDATE cities SET region_slug = 'grodno' WHERE slug IN ('grodno-city', 'mir')");
        $this->addSql("UPDATE cities SET region_slug = 'mogilev' WHERE slug = 'mogilev-city'");
        $this->addSql('CREATE INDEX IDX_cities_region_slug ON cities (region_slug)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_cities_region_slug');
        $this->addSql('ALTER TABLE cities DROP region_slug');
    }
}
