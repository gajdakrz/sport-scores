<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251001204149 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Competition table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
            CREATE TABLE competition (
                id SERIAL NOT NULL,
                sport_id INT NOT NULL,
                created_user_id INT NOT NULL,
                modified_user_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                is_active BOOLEAN DEFAULT true NOT NULL,
                gender VARCHAR(255) NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
                modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY(id)
            )'
        );
        $this->addSql('CREATE INDEX IDX_B50A2CB1AC78BCF8 ON competition (sport_id)');
        $this->addSql('CREATE INDEX IDX_B50A2CB1E104C1D3 ON competition (created_user_id)');
        $this->addSql('CREATE INDEX IDX_B50A2CB1BAA24139 ON competition (modified_user_id)');
        $this->addSql('COMMENT ON COLUMN competition.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN competition.modified_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE competition ADD CONSTRAINT FK_B50A2CB1AC78BCF8 FOREIGN KEY (sport_id) REFERENCES sport (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE competition ADD CONSTRAINT FK_B50A2CB1E104C1D3 FOREIGN KEY (created_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE competition ADD CONSTRAINT FK_B50A2CB1BAA24139 FOREIGN KEY (modified_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE competition DROP CONSTRAINT FK_B50A2CB1AC78BCF8');
        $this->addSql('ALTER TABLE competition DROP CONSTRAINT FK_B50A2CB1E104C1D3');
        $this->addSql('ALTER TABLE competition DROP CONSTRAINT FK_B50A2CB1BAA24139');
        $this->addSql('DROP TABLE competition');
    }
}
