<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260221210542 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change country_id to origin_country_id in person table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE person DROP CONSTRAINT fk_34dcd176f92f3e70');
        $this->addSql('DROP INDEX idx_34dcd176f92f3e70');
        $this->addSql('ALTER TABLE person RENAME COLUMN country_id TO origin_country_id');
        $this->addSql('ALTER TABLE person ALTER origin_country_id DROP NOT NULL');
        $this->addSql('ALTER TABLE person ADD CONSTRAINT FK_34DCD17640F4643D FOREIGN KEY (origin_country_id) REFERENCES country (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_34DCD17640F4643D ON person (origin_country_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE person DROP CONSTRAINT FK_34DCD17640F4643D');
        $this->addSql('DROP INDEX IDX_34DCD17640F4643D');
        $this->addSql('ALTER TABLE person RENAME COLUMN origin_country_id TO country_id');
        $this->addSql('ALTER TABLE person ALTER country_id SET NOT NULL');
        $this->addSql('ALTER TABLE person ADD CONSTRAINT fk_34dcd176f92f3e70 FOREIGN KEY (country_id) REFERENCES country (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_34dcd176f92f3e70 ON person (country_id)');
    }
}
