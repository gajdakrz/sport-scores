<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251123232222 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Dodanie warunku na uzupełnienie score w game_result';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE game_result
            ADD CONSTRAINT check_at_least_one_score
            CHECK (match_score IS NOT NULL OR ranking_score IS NOT NULL)
        ');

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_result DROP CONSTRAINT IF EXISTS check_at_least_one_score');
    }
}
