<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * SV-1.1 — Create user and auth_token tables for email/password login.
 */
final class Version20260425000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user and auth_token tables (SV-1.1)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE "user" (
                id UUID NOT NULL,
                email VARCHAR(180) NOT NULL,
                password VARCHAR(255) NOT NULL,
                roles JSON NOT NULL,
                created_at TIMESTAMPTZ NOT NULL,
                PRIMARY KEY (id)
            )
        SQL);
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');

        $this->addSql(<<<'SQL'
            CREATE TABLE auth_token (
                id UUID NOT NULL,
                user_id UUID NOT NULL,
                token VARCHAR(64) NOT NULL,
                created_at TIMESTAMPTZ NOT NULL,
                expires_at TIMESTAMPTZ NOT NULL,
                PRIMARY KEY (id)
            )
        SQL);
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9315F04E5F37A13B ON auth_token (token)');
        $this->addSql('CREATE INDEX IDX_9315F04EA76ED395 ON auth_token (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE auth_token
                ADD CONSTRAINT FK_9315F04EA76ED395
                FOREIGN KEY (user_id) REFERENCES "user" (id)
                ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auth_token DROP CONSTRAINT FK_9315F04EA76ED395');
        $this->addSql('DROP TABLE auth_token');
        $this->addSql('DROP TABLE "user"');
    }
}
