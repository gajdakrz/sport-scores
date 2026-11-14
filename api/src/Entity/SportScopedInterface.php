<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Entity that belongs to one sport and can be deactivated (e.g. a team or a competition).
 */
interface SportScopedInterface
{
    public function getSport(): ?Sport;

    public function isActive(): bool;
}
