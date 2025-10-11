<?php

declare(strict_types=1);

namespace App\Enum;

enum TeamType: string {
    case NATIONAL = 'national';
    case CLUB = 'club';
    case ACADEMIC = 'academic';
    case JUNIOR = 'junior';
    case REGIONAL = 'regional';
    case AMATEUR = 'amateur';
    case DOUBLES = 'doubles';
    case MIXED_DOUBLES = 'mixed_doubles';
}
