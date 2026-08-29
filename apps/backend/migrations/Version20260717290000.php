<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717290000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move commercial from deal_type to listing_type so business can be sale or rent';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE listings
            SET listing_type = 'commercial',
                deal_type = 'sale',
                rent_term = NULL
            WHERE deal_type = 'commercial'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            UPDATE listings
            SET deal_type = 'commercial',
                rent_term = NULL
            WHERE listing_type = 'commercial'
        ");
    }
}
