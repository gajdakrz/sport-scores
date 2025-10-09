<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251009220933 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
            CREATE TABLE game (
                id SERIAL NOT NULL,
                name VARCHAR(255) DEFAULT NULL,
                is_active BOOLEAN DEFAULT true NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
                created_user_id INT NOT NULL,
                modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
                modified_user_id INT NOT NULL,
                event_id INT NOT NULL,
                PRIMARY KEY(id)
            )'
        );
        $this->addSql('CREATE INDEX IDX_232B318CE104C1D3 ON game (created_user_id)');
        $this->addSql('CREATE INDEX IDX_232B318CBAA24139 ON game (modified_user_id)');
        $this->addSql('CREATE INDEX IDX_232B318C71F7E88B ON game (event_id)');
        $this->addSql('COMMENT ON COLUMN game.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN game.modified_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CE104C1D3 FOREIGN KEY (created_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CBAA24139 FOREIGN KEY (modified_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C71F7E88B FOREIGN KEY (event_id) REFERENCES event (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game DROP CONSTRAINT FK_232B318CE104C1D3');
        $this->addSql('ALTER TABLE game DROP CONSTRAINT FK_232B318CBAA24139');
        $this->addSql('ALTER TABLE game DROP CONSTRAINT FK_232B318C71F7E88B');
        $this->addSql('DROP TABLE game');
    }
}
