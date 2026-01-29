<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260129111101 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // 1. Add the new item_code column
        // $this->addSql('ALTER TABLE item ADD item_code VARCHAR(50) DEFAULT NULL');

        // 2. FIXED: Change VARCHAR(63) to VARCHAR(255) to prevent truncation error
        // We also remove 'DROP INDEX' if it's not absolutely necessary, 
        // but if you are changing length, Doctrine often drops/re-adds indexes.
        // For safety, let's just keep the length at 255.
        $this->addSql('ALTER TABLE school CHANGE name name VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE item DROP item_code');
        $this->addSql('ALTER TABLE school CHANGE name name VARCHAR(255) NOT NULL');
    }
}