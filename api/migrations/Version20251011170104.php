<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251011170104 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add team table and update person';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
            CREATE TABLE team (
                id SERIAL NOT NULL,
                name VARCHAR(255) NOT NULL,
                country_id INT DEFAULT NULL,
                team_type VARCHAR(255) NOT NULL,
                is_active BOOLEAN DEFAULT true NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
                created_user_id INT NOT NULL,
                modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
                modified_user_id INT NOT NULL,
                PRIMARY KEY(id)
            )'
        );
        $this->addSql('CREATE INDEX IDX_C4E0A61FE104C1D3 ON team (created_user_id)');
        $this->addSql('CREATE INDEX IDX_C4E0A61FBAA24139 ON team (modified_user_id)');
        $this->addSql('CREATE INDEX IDX_C4E0A61FF92F3E70 ON team (country_id)');
        $this->addSql('COMMENT ON COLUMN team.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN team.modified_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61FE104C1D3 FOREIGN KEY (created_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61FBAA24139 FOREIGN KEY (modified_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61FF92F3E70 FOREIGN KEY (country_id) REFERENCES country (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE person ADD gender VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE team DROP CONSTRAINT FK_C4E0A61FE104C1D3');
        $this->addSql('ALTER TABLE team DROP CONSTRAINT FK_C4E0A61FBAA24139');
        $this->addSql('ALTER TABLE team DROP CONSTRAINT FK_C4E0A61FF92F3E70');
        $this->addSql('DROP TABLE team');
        $this->addSql('ALTER TABLE person DROP gender');
    }
}
