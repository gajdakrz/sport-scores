<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261108185058 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add nullable competition.country_id (NULL = international competition)';
    }

    public function up(Schema $schema): void
    {
        // Existing competitions stay international (NULL), so every team remains allowed.
        $this->addSql('ALTER TABLE competition ADD country_id INT DEFAULT NULL');
        $this->addSql(
            'ALTER TABLE competition ADD CONSTRAINT FK_B50A2CB1F92F3E70 FOREIGN KEY (country_id) '
            . 'REFERENCES country (id) NOT DEFERRABLE INITIALLY IMMEDIATE'
        );
        $this->addSql('CREATE INDEX IDX_B50A2CB1F92F3E70 ON competition (country_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE competition DROP CONSTRAINT FK_B50A2CB1F92F3E70');
        $this->addSql('DROP INDEX IDX_B50A2CB1F92F3E70');
        $this->addSql('ALTER TABLE competition DROP country_id');
    }
}
