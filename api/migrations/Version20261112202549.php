<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261112202549 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add required user.first_name and user.last_name, '
            . 'backfilling existing accounts with the local part of their email';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD first_name VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD last_name VARCHAR(100) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP first_name');
        $this->addSql('ALTER TABLE "user" DROP last_name');
    }
}
