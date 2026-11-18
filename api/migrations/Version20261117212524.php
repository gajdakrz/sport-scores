<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Unique business keys of active records:
 * - names compare regardless of letter case: a nondeterministic ICU collation on the name columns
 *   ("Real Madrid" = "real madrid", accents still distinct; the typed letters are stored unchanged),
 * - partial unique indexes on the keys – only active (not soft-deleted) rows. PostgreSQL treats NULLs as distinct,
 *   so a key with a nullable column gets an extra index without that column, limited to rows where it IS NULL.
 * The collation is set before the indexes are created, so they are built once, already case-insensitive.
 */
final class Version20261117212524 extends AbstractMigration
{
    private const string ACTIVE = '(is_active = true)';

    private const array CASE_INSENSITIVE_NAME_TABLES = [
        'sport',
        'country',
        'competition',
        'event',
        'member_position',
        'team',
    ];

    /**
     * @var array<string, array{string, string, string}> index name => [table, columns, predicate]
     */
    private const array INDEXES = [
        'uniq_sport_name' => ['sport', 'name', self::ACTIVE],
        'uniq_country_name' => ['country', 'name', self::ACTIVE],
        'uniq_season_years' => ['season', 'start_year, end_year', self::ACTIVE],
        'uniq_member_position_sport_name' => ['member_position', 'sport_id, name', self::ACTIVE],
        'uniq_event_competition_name' => ['event', 'competition_id, name', self::ACTIVE],
        'uniq_competition_key' => ['competition', 'name, sport_id, gender, country_id', self::ACTIVE],
        'uniq_competition_key_international' => [
            'competition',
            'name, sport_id, gender',
            '((country_id IS NULL) AND (is_active = true))',
        ],
        'uniq_team_key' => [
            'team',
            'name, sport_id, gender, team_type, country_id, competition_country_id',
            self::ACTIVE,
        ],
        'uniq_team_key_no_country' => [
            'team',
            'name, sport_id, gender, team_type, competition_country_id',
            '((country_id IS NULL) AND (is_active = true))',
        ],
        'uniq_team_key_no_competition_country' => [
            'team',
            'name, sport_id, gender, team_type, country_id',
            '((competition_country_id IS NULL) AND (is_active = true))',
        ],
        'uniq_team_key_no_countries' => [
            'team',
            'name, sport_id, gender, team_type',
            '((country_id IS NULL) AND (competition_country_id IS NULL) AND (is_active = true))',
        ],
        'uniq_game_result_game_team' => ['game_result', 'game_id, team_id', self::ACTIVE],
        'uniq_game_result_game_person' => ['game_result', 'game_id, person_id', self::ACTIVE],
        'uniq_team_member_team_person_season' => ['team_member', 'team_id, person_id, start_season_id', self::ACTIVE],
    ];

    public function getDescription(): string
    {
        return 'Case-insensitive name collation and partial unique indexes on business keys of active records';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE COLLATION IF NOT EXISTS case_insensitive'
            . " (provider = icu, locale = 'und-u-ks-level2', deterministic = false)"
        );

        foreach (self::CASE_INSENSITIVE_NAME_TABLES as $table) {
            $this->addSql(sprintf('ALTER TABLE %s ALTER name TYPE VARCHAR(255) COLLATE "case_insensitive"', $table));
        }

        foreach (self::INDEXES as $name => [$table, $columns, $predicate]) {
            $this->addSql(sprintf('CREATE UNIQUE INDEX %s ON %s (%s) WHERE %s', $name, $table, $columns, $predicate));
        }
    }

    public function down(Schema $schema): void
    {
        foreach (array_keys(self::INDEXES) as $name) {
            $this->addSql(sprintf('DROP INDEX %s', $name));
        }

        foreach (self::CASE_INSENSITIVE_NAME_TABLES as $table) {
            $this->addSql(sprintf('ALTER TABLE %s ALTER name TYPE VARCHAR(255) COLLATE "default"', $table));
        }

        $this->addSql('DROP COLLATION case_insensitive');
    }
}
