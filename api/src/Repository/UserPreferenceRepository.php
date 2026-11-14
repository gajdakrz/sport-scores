<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserPreference;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractRepository<UserPreference>
 */
class UserPreferenceRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPreference::class);
    }

    /**
     * The stored preferences, or new (not persisted) ones with default values.
     */
    public function findOrCreateForUser(User $user): UserPreference
    {
        return $this->findOneBy(['user' => $user]) ?? new UserPreference($user);
    }
}
