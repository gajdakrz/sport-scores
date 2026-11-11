<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Gender category of a team or competition (men's, women's, mixed).
 * Persons use {@see Gender}; enums can't extend each other, so the two are linked by composition.
 */
enum GenderCategory: string
{
    use EnumLabelTrait;

    case MALE = 'male';
    case FEMALE = 'female';
    case MIXED = 'mixed';

    public static function fromGender(Gender $gender): self
    {
        return self::from($gender->value);
    }

    /**
     * Membership rule: a mixed category accepts persons of every gender.
     */
    public function accepts(Gender $gender): bool
    {
        return $this === self::MIXED || $this === self::fromGender($gender);
    }

    /**
     * Person gender required by this category; null for MIXED (no restriction).
     */
    public function toGender(): ?Gender
    {
        return Gender::tryFrom($this->value);
    }
}
