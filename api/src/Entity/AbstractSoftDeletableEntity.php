<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entity that is deactivated (isActive = false) instead of removed.
 */
#[ORM\MappedSuperclass]
abstract class AbstractSoftDeletableEntity extends AbstractEntity
{
    /**
     * Validation group of UniqueEntity constraints – placed last in each entity's GroupSequence,
     * so a duplicate is reported (as 409) only for otherwise valid data.
     */
    public const string UNIQUE_GROUP = 'Unique';

    /**
     * Partial unique indexes cover active rows only – a soft-deleted record doesn't block a new one.
     */
    public const string ACTIVE_ONLY = '(is_active = true)';

    /**
     * PostgreSQL ICU collation (created in a migration) for names that are unique regardless of letter case:
     * "Real Madrid" = "real madrid" in comparisons and unique indexes, while the typed letters are stored as is.
     */
    public const string CASE_INSENSITIVE = 'case_insensitive';

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => 1])]
    protected bool $isActive = true;

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }
}
