<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251120225621 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
            CREATE TABLE season (
                id SERIAL NOT NULL,
                created_user_id INT NOT NULL,
                modified_user_id INT NOT NULL,
                is_active BOOLEAN DEFAULT true NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
                modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
                start_year SMALLINT NOT NULL,
                end_year SMALLINT NOT NULL,
                PRIMARY KEY(id)
            )'
        );
        $this->addSql('CREATE INDEX IDX_F0E45BA9E104C1D3 ON season (created_user_id)');
        $this->addSql('CREATE INDEX IDX_F0E45BA9BAA24139 ON season (modified_user_id)');
        $this->addSql('COMMENT ON COLUMN season.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN season.modified_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE season ADD CONSTRAINT FK_F0E45BA9E104C1D3 FOREIGN KEY (created_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE season ADD CONSTRAINT FK_F0E45BA9BAA24139 FOREIGN KEY (modified_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game ADD season_id INT NOT NULL');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C4EC001D1 FOREIGN KEY (season_id) REFERENCES season (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_232B318C4EC001D1 ON game (season_id)');
        $this->addSql('ALTER TABLE team_member ADD season_id INT NOT NULL');
        $this->addSql('ALTER TABLE team_member ADD CONSTRAINT FK_6FFBDA14EC001D1 FOREIGN KEY (season_id) REFERENCES season (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6FFBDA14EC001D1 ON team_member (season_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE game DROP CONSTRAINT FK_232B318C4EC001D1');
        $this->addSql('ALTER TABLE team_member DROP CONSTRAINT FK_6FFBDA14EC001D1');
        $this->addSql('ALTER TABLE season DROP CONSTRAINT FK_F0E45BA9E104C1D3');
        $this->addSql('ALTER TABLE season DROP CONSTRAINT FK_F0E45BA9BAA24139');
        $this->addSql('DROP TABLE season');
        $this->addSql('DROP INDEX IDX_6FFBDA14EC001D1');
        $this->addSql('ALTER TABLE team_member DROP season_id');
        $this->addSql('DROP INDEX IDX_232B318C4EC001D1');
        $this->addSql('ALTER TABLE game DROP season_id');
    }
}
