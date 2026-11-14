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
