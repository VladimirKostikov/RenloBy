<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717340000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Hide dual-seed public copies: soft-delete is_test=false listings/articles that have an is_test=true twin';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE listings AS pub
            SET deleted_at = NOW()
            WHERE pub.is_test = FALSE
              AND pub.deleted_at IS NULL
              AND EXISTS (
                SELECT 1
                FROM listings AS tst
                WHERE tst.is_test = TRUE
                  AND tst.deleted_at IS NULL
                  AND tst.address = pub.address
                  AND tst.user_id = pub.user_id
                  AND tst.deal_type = pub.deal_type
                  AND tst.id <> pub.id
              )
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE articles AS pub
            SET deleted_at = NOW()
            WHERE pub.is_test = FALSE
              AND pub.deleted_at IS NULL
              AND EXISTS (
                SELECT 1
                FROM articles AS tst
                WHERE tst.is_test = TRUE
                  AND tst.deleted_at IS NULL
                  AND tst.slug = pub.slug
                  AND tst.id <> pub.id
              )
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE listings AS pub
            SET deleted_at = NULL
            WHERE pub.is_test = FALSE
              AND pub.deleted_at IS NOT NULL
              AND EXISTS (
                SELECT 1
                FROM listings AS tst
                WHERE tst.is_test = TRUE
                  AND tst.deleted_at IS NULL
                  AND tst.address = pub.address
                  AND tst.user_id = pub.user_id
                  AND tst.deal_type = pub.deal_type
                  AND tst.id <> pub.id
              )
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE articles AS pub
            SET deleted_at = NULL
            WHERE pub.is_test = FALSE
              AND pub.deleted_at IS NOT NULL
              AND EXISTS (
                SELECT 1
                FROM articles AS tst
                WHERE tst.is_test = TRUE
                  AND tst.deleted_at IS NULL
                  AND tst.slug = pub.slug
                  AND tst.id <> pub.id
              )
        SQL);
    }
}
