<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251020193426 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE budget_limits (id UUID NOT NULL, limit_amount JSONB NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, budget_id UUID NOT NULL, subcategory_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_2767E0736ABA6B8 ON budget_limits (budget_id)');
        $this->addSql('CREATE INDEX IDX_2767E075DC6FE57 ON budget_limits (subcategory_id)');
        $this->addSql('CREATE UNIQUE INDEX uq_budget_limit_budget_subcategory ON budget_limits (budget_id, subcategory_id)');
        $this->addSql('CREATE TABLE budgets (id UUID NOT NULL, year SMALLINT NOT NULL, month SMALLINT NOT NULL, planned_income JSONB NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_DCAA9548A76ED395 ON budgets (user_id)');
        $this->addSql('CREATE UNIQUE INDEX uq_budget_user_year_month ON budgets (user_id, year, month)');
        $this->addSql('CREATE TABLE subcategories (id UUID NOT NULL, name VARCHAR(100) NOT NULL, type VARCHAR(10) NOT NULL, main_category VARCHAR(50) NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_6562A1CBA76ED395 ON subcategories (user_id)');
        $this->addSql('CREATE UNIQUE INDEX uq_subcategory_user_name ON subcategories (user_id, name)');
        $this->addSql('CREATE TABLE transactions (id UUID NOT NULL, amount JSONB NOT NULL, date DATE NOT NULL, description VARCHAR(200) DEFAULT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, user_id UUID NOT NULL, subcategory_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_EAA81A4CA76ED395 ON transactions (user_id)');
        $this->addSql('CREATE INDEX IDX_EAA81A4C5DC6FE57 ON transactions (subcategory_id)');
        $this->addSql('CREATE TABLE users (id UUID NOT NULL, email VARCHAR(255) NOT NULL, password_hash VARCHAR(255) NOT NULL, billing_cycle_start_day SMALLINT DEFAULT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('ALTER TABLE budget_limits ADD CONSTRAINT FK_2767E0736ABA6B8 FOREIGN KEY (budget_id) REFERENCES budgets (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE budget_limits ADD CONSTRAINT FK_2767E075DC6FE57 FOREIGN KEY (subcategory_id) REFERENCES subcategories (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE budgets ADD CONSTRAINT FK_DCAA9548A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE subcategories ADD CONSTRAINT FK_6562A1CBA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_EAA81A4CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_EAA81A4C5DC6FE57 FOREIGN KEY (subcategory_id) REFERENCES subcategories (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE budget_limits DROP CONSTRAINT FK_2767E0736ABA6B8');
        $this->addSql('ALTER TABLE budget_limits DROP CONSTRAINT FK_2767E075DC6FE57');
        $this->addSql('ALTER TABLE budgets DROP CONSTRAINT FK_DCAA9548A76ED395');
        $this->addSql('ALTER TABLE subcategories DROP CONSTRAINT FK_6562A1CBA76ED395');
        $this->addSql('ALTER TABLE transactions DROP CONSTRAINT FK_EAA81A4CA76ED395');
        $this->addSql('ALTER TABLE transactions DROP CONSTRAINT FK_EAA81A4C5DC6FE57');
        $this->addSql('DROP TABLE budget_limits');
        $this->addSql('DROP TABLE budgets');
        $this->addSql('DROP TABLE subcategories');
        $this->addSql('DROP TABLE transactions');
        $this->addSql('DROP TABLE users');
    }
}
