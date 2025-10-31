<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251020193453 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds RLS policies and indexes for data isolation';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX idx_transactions_user_date ON transactions (user_id, date DESC)');

        // Enable RLS
        $this->addSql('ALTER TABLE subcategories ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE transactions ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE budgets ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE budget_limits ENABLE ROW LEVEL SECURITY');

        // Policies
        $this->addSql("
            CREATE POLICY user_data_isolation_policy ON subcategories
            FOR ALL
            USING (user_id = current_setting('app.current_user_id')::UUID)
        ");

        $this->addSql("
            CREATE POLICY user_data_isolation_policy ON transactions
            FOR ALL
            USING (user_id = current_setting('app.current_user_id')::UUID)
        ");

        $this->addSql("
            CREATE POLICY user_data_isolation_policy ON budgets
            FOR ALL
            USING (user_id = current_setting('app.current_user_id')::UUID)
        ");

        $this->addSql("
            CREATE POLICY user_data_isolation_policy ON budget_limits
            FOR ALL
            USING (
                EXISTS (
                    SELECT 1 FROM budgets
                    WHERE id = budget_limits.budget_id
                    AND user_id = current_setting('app.current_user_id')::UUID
                )
            )
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_transactions_user_date');

        // Policies
        $this->addSql('DROP POLICY user_data_isolation_policy ON subcategories');
        $this->addSql('DROP POLICY user_data_isolation_policy ON transactions');
        $this->addSql('DROP POLICY user_data_isolation_policy ON budgets');
        $this->addSql('DROP POLICY user_data_isolation_policy ON budget_limits');

        // Disable RLS
        $this->addSql('ALTER TABLE subcategories DISABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE transactions DISABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE budgets DISABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE budget_limits DISABLE ROW LEVEL SECURITY');
    }
}
