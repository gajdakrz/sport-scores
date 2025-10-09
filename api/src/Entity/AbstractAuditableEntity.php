<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

#[ORM\MappedSuperclass]
abstract class AbstractAuditableEntity extends AbstractEntity
{
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(
        name: 'created_user_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    protected User $createdBy;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(
        name: 'modified_user_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    protected User $modifiedBy;

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getModifiedBy(): User
    {
        return $this->modifiedBy;
    }

    public function setModifiedBy(User $modifiedBy): static
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }
}
