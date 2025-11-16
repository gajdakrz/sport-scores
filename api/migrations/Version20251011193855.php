<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251011193855 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add team_member table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
            CREATE TABLE team_member (
                id SERIAL NOT NULL,
                team_id INT NOT NULL,
                person_id INT NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE DEFAULT NULL,
                is_active BOOLEAN DEFAULT true NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
                created_user_id INT NOT NULL,
                modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
                modified_user_id INT NOT NULL,
                PRIMARY KEY(id)
            )'
        );
        $this->addSql('CREATE INDEX IDX_DE0C496FE104C1D3 ON team_member (created_user_id)');
        $this->addSql('CREATE INDEX IDX_DE0C496FBAA24139 ON team_member (modified_user_id)');
        $this->addSql('CREATE INDEX IDX_DE0C496F296CD8AE ON team_member (team_id)');
        $this->addSql('CREATE INDEX IDX_DE0C496F217BBB47 ON team_member (person_id)');
        $this->addSql('COMMENT ON COLUMN team_member.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN team_member.modified_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE team_member ADD CONSTRAINT FK_DE0C496FE104C1D3 FOREIGN KEY (created_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team_member ADD CONSTRAINT FK_DE0C496FBAA24139 FOREIGN KEY (modified_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team_member ADD CONSTRAINT FK_DE0C496F296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team_member ADD CONSTRAINT FK_DE0C496F217BBB47 FOREIGN KEY (person_id) REFERENCES person (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE team_member DROP CONSTRAINT FK_DE0C496FE104C1D3');
        $this->addSql('ALTER TABLE team_member DROP CONSTRAINT FK_DE0C496FBAA24139');
        $this->addSql('ALTER TABLE team_member DROP CONSTRAINT FK_DE0C496F296CD8AE');
        $this->addSql('ALTER TABLE team_member DROP CONSTRAINT FK_DE0C496F217BBB47');
        $this->addSql('DROP TABLE team_member');
    }
}
