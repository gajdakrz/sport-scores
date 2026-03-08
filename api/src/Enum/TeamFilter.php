<?php

declare(strict_types=1);

namespace App\Enum;

enum TeamFilter: string
{
    case INCLUDED = 'included';
    case EXCLUDED = 'excluded';
    case ALL = 'all';
}
