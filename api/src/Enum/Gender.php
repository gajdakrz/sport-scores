<?php

declare(strict_types=1);

namespace App\Enum;

use App\Enum\Trait\EnumLabelTrait;

enum Gender: string
{
    use EnumLabelTrait; // enums can't use inheritance, but they can use traits

    case MALE = 'male';
    case FEMALE = 'female';
}
