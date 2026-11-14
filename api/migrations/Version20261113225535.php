<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261113225535 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_preference: favourite sport, competition and team, '
            . '"Current season" filter on by default';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_preference (
            id SERIAL NOT NULL,
            user_id INT NOT NULL,
            favourite_sport_id INT DEFAULT NULL,
            favourite_competition_id INT DEFAULT NULL,
            favourite_team_id INT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
            modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
            current_season_by_default BOOLEAN DEFAULT true NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FA0E76BFA76ED395 ON user_preference (user_id)');
        $this->addSql('CREATE INDEX IDX_FA0E76BFFCB53A5B ON user_preference (favourite_sport_id)');
        $this->addSql('CREATE INDEX IDX_FA0E76BF328CED0B ON user_preference (favourite_competition_id)');
        $this->addSql('CREATE INDEX IDX_FA0E76BFE19B47DE ON user_preference (favourite_team_id)');
        $this->addSql('COMMENT ON COLUMN user_preference.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN user_preference.modified_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user_preference ADD CONSTRAINT FK_FA0E76BFA76ED395 FOREIGN KEY (user_id)'
            . ' REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_preference ADD CONSTRAINT FK_FA0E76BFFCB53A5B FOREIGN KEY (favourite_sport_id)'
            . ' REFERENCES sport (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_preference ADD CONSTRAINT FK_FA0E76BF328CED0B'
            . ' FOREIGN KEY (favourite_competition_id)'
            . ' REFERENCES competition (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_preference ADD CONSTRAINT FK_FA0E76BFE19B47DE FOREIGN KEY (favourite_team_id)'
            . ' REFERENCES team (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_preference');
    }
}
