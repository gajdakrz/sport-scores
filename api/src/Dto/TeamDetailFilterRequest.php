<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\TeamType;
use Symfony\Component\Validator\Constraints as Assert;

class TeamDetailFilterRequest extends PaginationRequest
{
}
