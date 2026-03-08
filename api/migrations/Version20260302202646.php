<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260302202646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE team_member DROP CONSTRAINT fk_6ffbda14ec001d1');
        $this->addSql('DROP INDEX idx_6ffbda14ec001d1');
        $this->addSql('ALTER TABLE team_member ADD is_current_member BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE team_member DROP start_date');
        $this->addSql('ALTER TABLE team_member DROP end_date');
        $this->addSql('ALTER TABLE team_member RENAME COLUMN season_id TO start_season_id');
        $this->addSql('ALTER TABLE team_member ADD CONSTRAINT FK_6FFBDA186087A17 FOREIGN KEY (start_season_id) REFERENCES season (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6FFBDA186087A17 ON team_member (start_season_id)');
        $this->addSql('ALTER TABLE person ADD current_team_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE person ADD CONSTRAINT FK_34DCD17683615D2B FOREIGN KEY (current_team_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_34DCD17683615D2B ON person (current_team_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE team_member DROP CONSTRAINT FK_6FFBDA186087A17');
        $this->addSql('DROP INDEX IDX_6FFBDA186087A17');
        $this->addSql('ALTER TABLE team_member ADD start_date DATE DEFAULT CURRENT_DATE NOT NULL');
        $this->addSql('ALTER TABLE team_member ADD end_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE team_member DROP is_current_member');
        $this->addSql('ALTER TABLE team_member RENAME COLUMN start_season_id TO season_id');
        $this->addSql('COMMENT ON COLUMN team_member.start_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN team_member.end_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE team_member ADD CONSTRAINT fk_6ffbda14ec001d1 FOREIGN KEY (season_id) REFERENCES season (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_6ffbda14ec001d1 ON team_member (season_id)');
        $this->addSql('ALTER TABLE person DROP CONSTRAINT FK_34DCD17683615D2B');
        $this->addSql('DROP INDEX IDX_34DCD17683615D2B');
        $this->addSql('ALTER TABLE person DROP current_team_id');
    }
}
