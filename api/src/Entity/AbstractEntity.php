<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Base of every entity: creation and modification time, maintained by lifecycle callbacks – never set them manually.
 */
#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractEntity
{
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    protected DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    protected DateTimeImmutable $modifiedAt;

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getModifiedAt(): DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    #[ORM\PrePersist]
    public function setCreatedAtOnPersist(): void
    {
        $this->createdAt = $this->modifiedAt = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setModifiedAtOnUpdate(): void
    {
        $this->modifiedAt = new DateTimeImmutable();
    }
}
