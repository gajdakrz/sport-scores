<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251010213859 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add person table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
            CREATE TABLE person (
                id SERIAL NOT NULL,
                first_name VARCHAR(255) NOT NULL,
                last_name VARCHAR(255) NOT NULL,
                country_id INT NOT NULL,
                is_active BOOLEAN DEFAULT true NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
                created_user_id INT NOT NULL,
                modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
                modified_user_id INT NOT NULL,
                PRIMARY KEY(id)
            )'
        );
        $this->addSql('CREATE INDEX IDX_34DCD176E104C1D3 ON person (created_user_id)');
        $this->addSql('CREATE INDEX IDX_34DCD176BAA24139 ON person (modified_user_id)');
        $this->addSql('CREATE INDEX IDX_34DCD176F92F3E70 ON person (country_id)');
        $this->addSql('COMMENT ON COLUMN person.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN person.modified_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE person ADD CONSTRAINT FK_34DCD176E104C1D3 FOREIGN KEY (created_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE person ADD CONSTRAINT FK_34DCD176BAA24139 FOREIGN KEY (modified_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE person ADD CONSTRAINT FK_34DCD176F92F3E70 FOREIGN KEY (country_id) REFERENCES country (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE person DROP CONSTRAINT FK_34DCD176E104C1D3');
        $this->addSql('ALTER TABLE person DROP CONSTRAINT FK_34DCD176BAA24139');
        $this->addSql('ALTER TABLE person DROP CONSTRAINT FK_34DCD176F92F3E70');
        $this->addSql('DROP TABLE person');
    }
}
