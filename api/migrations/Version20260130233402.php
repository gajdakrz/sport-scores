<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260130233402 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add table member position';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
            CREATE TABLE member_position (
                id SERIAL NOT NULL,
                created_user_id INT NOT NULL,
                modified_user_id INT NOT NULL,
                sport_id INT NOT NULL,
                is_active BOOLEAN DEFAULT true NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
                modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
                name VARCHAR(255) NOT NULL,
                PRIMARY KEY(id)
            )'
        );
        $this->addSql('CREATE INDEX IDX_B1F1471FE104C1D3 ON member_position (created_user_id)');
        $this->addSql('CREATE INDEX IDX_B1F1471FBAA24139 ON member_position (modified_user_id)');
        $this->addSql('CREATE INDEX IDX_B1F1471FAC78BCF8 ON member_position (sport_id)');
        $this->addSql('COMMENT ON COLUMN member_position.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN member_position.modified_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE member_position ADD CONSTRAINT FK_B1F1471FE104C1D3 FOREIGN KEY (created_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE member_position ADD CONSTRAINT FK_B1F1471FBAA24139 FOREIGN KEY (modified_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE member_position ADD CONSTRAINT FK_B1F1471FAC78BCF8 FOREIGN KEY (sport_id) REFERENCES sport (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team_member ADD member_position_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE team_member ADD CONSTRAINT FK_6FFBDA14B93A84 FOREIGN KEY (member_position_id) REFERENCES member_position (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6FFBDA14B93A84 ON team_member (member_position_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE team_member DROP CONSTRAINT FK_6FFBDA14B93A84');
        $this->addSql('ALTER TABLE member_position DROP CONSTRAINT FK_B1F1471FE104C1D3');
        $this->addSql('ALTER TABLE member_position DROP CONSTRAINT FK_B1F1471FBAA24139');
        $this->addSql('ALTER TABLE member_position DROP CONSTRAINT FK_B1F1471FAC78BCF8');
        $this->addSql('DROP TABLE member_position');
        $this->addSql('DROP INDEX IDX_6FFBDA14B93A84');
        $this->addSql('ALTER TABLE team_member DROP member_position_id');
    }
}
