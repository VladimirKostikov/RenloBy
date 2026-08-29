<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add soft delete and is_test flags to all entities, create payment_transactions';
    }

    public function up(Schema $schema): void
    {
        $tables = [
            'articles',
            'cities',
            'comparisons',
            'districts',
            'favorites',
            'info_pages',
            'listings',
            'metro_stations',
            'saved_searches',
            'seo_meta',
            'users',
        ];

        foreach ($tables as $table) {
            $this->addSql(sprintf('ALTER TABLE %s ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL', $table));
            $this->addSql(sprintf('ALTER TABLE %s ADD is_test BOOLEAN DEFAULT false NOT NULL', $table));
            $this->addSql(sprintf('CREATE INDEX IDX_%s_deleted_at ON %s (deleted_at)', $table, $table));
            $this->addSql(sprintf('CREATE INDEX IDX_%s_is_test ON %s (is_test)', $table, $table));
        }

        $this->addSql('CREATE TABLE payment_transactions (
            id SERIAL NOT NULL,
            user_id INT NOT NULL,
            amount NUMERIC(12, 2) NOT NULL,
            currency VARCHAR(3) NOT NULL,
            status VARCHAR(32) NOT NULL,
            provider VARCHAR(32) NOT NULL,
            provider_payment_id VARCHAR(128) DEFAULT NULL,
            description VARCHAR(255) DEFAULT NULL,
            confirmation_url TEXT DEFAULT NULL,
            metadata JSON NOT NULL,
            is_test BOOLEAN DEFAULT false NOT NULL,
            deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_payment_transactions_user ON payment_transactions (user_id)');
        $this->addSql('CREATE INDEX IDX_payment_transactions_status ON payment_transactions (status)');
        $this->addSql('CREATE INDEX IDX_payment_transactions_provider_payment ON payment_transactions (provider_payment_id)');
        $this->addSql('CREATE INDEX IDX_payment_transactions_deleted_at ON payment_transactions (deleted_at)');
        $this->addSql('CREATE INDEX IDX_payment_transactions_is_test ON payment_transactions (is_test)');
        $this->addSql('ALTER TABLE payment_transactions ADD CONSTRAINT FK_payment_transactions_user FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment_transactions DROP CONSTRAINT FK_payment_transactions_user');
        $this->addSql('DROP TABLE payment_transactions');

        $tables = [
            'articles',
            'cities',
            'comparisons',
            'districts',
            'favorites',
            'info_pages',
            'listings',
            'metro_stations',
            'saved_searches',
            'seo_meta',
            'users',
        ];

        foreach ($tables as $table) {
            $this->addSql(sprintf('DROP INDEX IDX_%s_deleted_at', $table));
            $this->addSql(sprintf('DROP INDEX IDX_%s_is_test', $table));
            $this->addSql(sprintf('ALTER TABLE %s DROP deleted_at', $table));
            $this->addSql(sprintf('ALTER TABLE %s DROP is_test', $table));
        }
    }
}
