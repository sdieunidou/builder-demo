<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * SV-1.3 — Add failed_attempts and locked_until columns to user table for lockout.
 */
final class Version20260425000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add failed_attempts and locked_until columns to user table (SV-1.3)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD COLUMN failed_attempts INTEGER NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE "user" ADD COLUMN locked_until TIMESTAMPTZ DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP COLUMN locked_until');
        $this->addSql('ALTER TABLE "user" DROP COLUMN failed_attempts');
    }
}
