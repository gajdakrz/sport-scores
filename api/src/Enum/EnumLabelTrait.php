<?php

declare(strict_types=1);

namespace App\Enum;

trait EnumLabelTrait
{
    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', strtolower($this->name)));
    }
}
