<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261103192031 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add nullable team.gender (NULL = unspecified/mixed) and backfill it from active team members';
    }

    public function up(Schema $schema): void
    {
        // Existing teams start as 'mixed'; the default is only for this migration.
        $this->addSql("ALTER TABLE team ADD gender VARCHAR(255) DEFAULT 'mixed' NOT NULL");

        // Teams whose active members all share one gender get that gender;
        // mixed members or no members stay 'mixed'.
        $this->addSql(<<<'SQL'
          UPDATE team t
          SET gender = s.gender
          FROM (
              SELECT tm.team_id, MIN(p.gender) AS gender
              FROM team_member tm
              JOIN person p ON p.id = tm.person_id
              WHERE tm.is_active = true
              GROUP BY tm.team_id
              HAVING COUNT(DISTINCT p.gender) = 1
          ) s
          WHERE s.team_id = t.id
          SQL);

        $this->addSql('ALTER TABLE team ALTER gender DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE team DROP gender');
    }
}
