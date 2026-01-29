<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260129101543 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE email_template CHANGE `to` `to` JSON DEFAULT NULL, CHANGE cc cc JSON DEFAULT NULL, CHANGE bcc bcc JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE error_log CHANGE error_detail error_detail JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` CHANGE tax tax JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE sales_order CHANGE taxes taxes JSON DEFAULT NULL');
        // $this->addSql('ALTER TABLE school ADD school_code VARCHAR(50) DEFAULT NULL, ADD brand_code VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE setting CHANGE invoice_cc invoice_cc JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction CHANGE response response JSON DEFAULT NULL, CHANGE cart cart JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL');
        // $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649C32A47EE FOREIGN KEY (school_id) REFERENCES school (id)');
        // $this->addSql('CREATE INDEX IDX_8D93D649C32A47EE ON user (school_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE email_template CHANGE `to` `to` LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE cc cc LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE bcc bcc LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE error_log CHANGE error_detail error_detail LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE `order` CHANGE tax tax LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE sales_order CHANGE taxes taxes LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE school DROP school_code, DROP brand_code');
        $this->addSql('ALTER TABLE setting CHANGE invoice_cc invoice_cc LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE transaction CHANGE cart cart LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE response response LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649C32A47EE');
        $this->addSql('DROP INDEX IDX_8D93D649C32A47EE ON user');
        $this->addSql('ALTER TABLE user CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`');
    }
}
