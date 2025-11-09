<?php

declare(strict_types=1);

namespace App\Enum;

enum TeamType: string
{
    case NATIONAL = 'national';
    case CLUB = 'club';
    case ACADEMIC = 'academic';
    case JUNIOR = 'junior';
    case REGIONAL = 'regional';
    case AMATEUR = 'amateur';
    case DOUBLES = 'doubles';
    case MIXED_DOUBLES = 'mixed_doubles';

    public static function label(TeamType $enum): string
    {
        return ucfirst(str_replace('_', ' ', strtolower($enum->name)));
    }
}
