<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TeamMember;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractRepository<TeamMember>
 */
class TeamMemberRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamMember::class);
    }
}
