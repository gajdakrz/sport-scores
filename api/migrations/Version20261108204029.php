<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261108204029 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add nullable team.competition_country_id (NULL = not applicable, e.g. national team) '
            . 'and backfill it from country_id for non-national teams';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE team ADD competition_country_id INT DEFAULT NULL');
        $this->addSql(
            'ALTER TABLE team ADD CONSTRAINT FK_C4E0A61F5A38DE48 FOREIGN KEY (competition_country_id) '
            . 'REFERENCES country (id) NOT DEFERRABLE INITIALLY IMMEDIATE'
        );
        $this->addSql('CREATE INDEX IDX_C4E0A61F5A38DE48 ON team (competition_country_id)');

        // Existing non-national teams keep playing in national competitions of their country of origin;
        // national teams stay NULL (they play only international competitions).
        $this->addSql("UPDATE team SET competition_country_id = country_id WHERE team_type <> 'national'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE team DROP CONSTRAINT FK_C4E0A61F5A38DE48');
        $this->addSql('DROP INDEX IDX_C4E0A61F5A38DE48');
        $this->addSql('ALTER TABLE team DROP competition_country_id');
    }
}
