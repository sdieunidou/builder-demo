<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * SV-2.1 — Add digest_subscribed column to user table.
 */
final class Version20260425000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add digest_subscribed boolean column to user table (SV-2.1)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD COLUMN digest_subscribed BOOLEAN NOT NULL DEFAULT TRUE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP COLUMN digest_subscribed');
    }
}
