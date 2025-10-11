<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251010202938 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add country table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
            CREATE TABLE country (
                id SERIAL NOT NULL,
                name VARCHAR(255) NOT NULL,
                is_active BOOLEAN DEFAULT true NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
                created_user_id INT NOT NULL,
                modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
                modified_user_id INT NOT NULL,
                PRIMARY KEY(id)
            )'
        );
        $this->addSql('CREATE INDEX IDX_5373C966E104C1D3 ON country (created_user_id)');
        $this->addSql('CREATE INDEX IDX_5373C966BAA24139 ON country (modified_user_id)');
        $this->addSql('COMMENT ON COLUMN country.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN country.modified_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE country ADD CONSTRAINT FK_5373C966E104C1D3 FOREIGN KEY (created_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE country ADD CONSTRAINT FK_5373C966BAA24139 FOREIGN KEY (modified_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE country DROP CONSTRAINT FK_5373C966E104C1D3');
        $this->addSql('ALTER TABLE country DROP CONSTRAINT FK_5373C966BAA24139');
        $this->addSql('DROP TABLE country');
    }
}
