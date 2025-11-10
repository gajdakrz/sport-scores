<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251008134149 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Event table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
            CREATE TABLE event (
                id SERIAL NOT NULL,
                competition_id INT NOT NULL,
                created_user_id INT NOT NULL,
                modified_user_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                is_active BOOLEAN DEFAULT true NOT NULL,
                start_date DATE NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
                modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY(id)
            )'
        );
        $this->addSql('CREATE INDEX IDX_3BAE0AA77B39D312 ON event (competition_id)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7E104C1D3 ON event (created_user_id)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7BAA24139 ON event (modified_user_id)');
        $this->addSql('COMMENT ON COLUMN event.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN event.modified_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA77B39D312 FOREIGN KEY (competition_id) REFERENCES competition (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7E104C1D3 FOREIGN KEY (created_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7BAA24139 FOREIGN KEY (modified_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP CONSTRAINT FK_3BAE0AA77B39D312');
        $this->addSql('ALTER TABLE event DROP CONSTRAINT FK_3BAE0AA7E104C1D3');
        $this->addSql('ALTER TABLE event DROP CONSTRAINT FK_3BAE0AA7BAA24139');
        $this->addSql('DROP TABLE event');
    }
}
