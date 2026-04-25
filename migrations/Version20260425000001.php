<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * SV-1.2 — Add invalidated_at column to auth_token for soft-delete logout.
 */
final class Version20260425000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add invalidated_at column to auth_token (SV-1.2)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auth_token ADD COLUMN invalidated_at TIMESTAMPTZ DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auth_token DROP COLUMN invalidated_at');
    }
}
