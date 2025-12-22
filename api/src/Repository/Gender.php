<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\EnumLabelTrait;

enum Gender: string
{
    use EnumLabelTrait; // Enumy nie mogą używać dziedziczenia, ale mozna użyć trait

    case MALE = 'male';
    case FEMALE = 'female';
}
