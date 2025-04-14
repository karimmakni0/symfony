<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add isBanned field to users table
 */
final class Version20250402Add_IsBanned extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add isBanned field to users table';
    }

    public function up(Schema $schema): void
    {
        // Execute direct SQL to add the column
        $this->addSql('ALTER TABLE users ADD is_banned TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove the column if needed
        $this->addSql('ALTER TABLE users DROP COLUMN is_banned');
    }
}
