<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251207191200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Poprawki tabel';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game ADD date DATE DEFAULT CURRENT_DATE NOT NULL');
        $this->addSql('ALTER TABLE game DROP name');
        $this->addSql('COMMENT ON COLUMN game.date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE team_member ALTER COLUMN start_date SET DEFAULT CURRENT_DATE');
        $this->addSql('ALTER TABLE team_member ALTER end_date TYPE DATE');
        $this->addSql('COMMENT ON COLUMN team_member.start_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN team_member.end_date IS \'(DC2Type:date_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game ADD name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE game DROP date');
        $this->addSql('ALTER TABLE team_member ALTER start_date TYPE DATE');
        $this->addSql('ALTER TABLE team_member ALTER end_date TYPE DATE');
        $this->addSql('COMMENT ON COLUMN team_member.start_date IS NULL');
        $this->addSql('COMMENT ON COLUMN team_member.end_date IS NULL');
    }
}
