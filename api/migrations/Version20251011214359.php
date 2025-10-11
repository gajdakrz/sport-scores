<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251011214359 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add game_result table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
            CREATE TABLE game_result (
                id SERIAL NOT NULL,
                game_id INT NOT NULL,
                team_id INT DEFAULT NULL,
                person_id INT DEFAULT NULL,
                match_score INT NOT NULL,
                ranking_score INT NOT NULL,
                is_active BOOLEAN DEFAULT true NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
                created_user_id INT NOT NULL,
                modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
                modified_user_id INT NOT NULL,
                PRIMARY KEY(id)
            )'
        );
        $this->addSql('CREATE INDEX IDX_6E5F6CDBE104C1D3 ON game_result (created_user_id)');
        $this->addSql('CREATE INDEX IDX_6E5F6CDBBAA24139 ON game_result (modified_user_id)');
        $this->addSql('CREATE INDEX IDX_6E5F6CDBE48FD905 ON game_result (game_id)');
        $this->addSql('CREATE INDEX IDX_6E5F6CDB296CD8AE ON game_result (team_id)');
        $this->addSql('CREATE INDEX IDX_6E5F6CDB217BBB47 ON game_result (person_id)');
        $this->addSql('COMMENT ON COLUMN game_result.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN game_result.modified_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE game_result ADD CONSTRAINT FK_6E5F6CDBE104C1D3 FOREIGN KEY (created_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game_result ADD CONSTRAINT FK_6E5F6CDBBAA24139 FOREIGN KEY (modified_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game_result ADD CONSTRAINT FK_6E5F6CDBE48FD905 FOREIGN KEY (game_id) REFERENCES game (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game_result ADD CONSTRAINT FK_6E5F6CDB296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game_result ADD CONSTRAINT FK_6E5F6CDB217BBB47 FOREIGN KEY (person_id) REFERENCES person (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER INDEX idx_de0c496fe104c1d3 RENAME TO IDX_6FFBDA1E104C1D3');
        $this->addSql('ALTER INDEX idx_de0c496fbaa24139 RENAME TO IDX_6FFBDA1BAA24139');
        $this->addSql('ALTER INDEX idx_de0c496f296cd8ae RENAME TO IDX_6FFBDA1296CD8AE');
        $this->addSql('ALTER INDEX idx_de0c496f217bbb47 RENAME TO IDX_6FFBDA1217BBB47');
        $this->addSql('
            ALTER TABLE game_result
            ADD CONSTRAINT chk_team_or_person
            CHECK (
                (team_id IS NULL AND person_id IS NOT NULL)
                OR
                (team_id IS NOT NULL AND person_id IS NULL)
            )
        ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_result DROP CONSTRAINT FK_6E5F6CDBE104C1D3');
        $this->addSql('ALTER TABLE game_result DROP CONSTRAINT FK_6E5F6CDBBAA24139');
        $this->addSql('ALTER TABLE game_result DROP CONSTRAINT FK_6E5F6CDBE48FD905');
        $this->addSql('ALTER TABLE game_result DROP CONSTRAINT FK_6E5F6CDB296CD8AE');
        $this->addSql('ALTER TABLE game_result DROP CONSTRAINT FK_6E5F6CDB217BBB47');
        $this->addSql('DROP TABLE game_result');
        $this->addSql('ALTER INDEX idx_6ffbda1217bbb47 RENAME TO idx_de0c496f217bbb47');
        $this->addSql('ALTER INDEX idx_6ffbda1296cd8ae RENAME TO idx_de0c496f296cd8ae');
        $this->addSql('ALTER INDEX idx_6ffbda1baa24139 RENAME TO idx_de0c496fbaa24139');
        $this->addSql('ALTER INDEX idx_6ffbda1e104c1d3 RENAME TO idx_de0c496fe104c1d3');
    }
}
