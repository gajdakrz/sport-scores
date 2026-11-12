<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function nameProvider(): array
    {
        return [
            'regular name'      => ['Jan', 'Kowalski', 'Jan Kowalski', 'JK'],
            'polish letters'    => ['łukasz', 'śliwa', 'łukasz śliwa', 'ŁŚ'],
            'surrounding space' => [' Anna ', ' Nowak ', 'Anna Nowak', 'AN'],
            'no last name'      => ['gajda.krzysztof', '', 'gajda.krzysztof', 'G'],
        ];
    }

    #[Test]
    #[DataProvider('nameProvider')]
    #[TestDox('User with $_dataName has the expected full name and initials')]
    public function userHasFullNameAndInitials(
        string $firstName,
        string $lastName,
        string $fullName,
        string $initials,
    ): void {
        $user = (new User())->setFirstName($firstName)->setLastName($lastName);

        $this->assertSame($fullName, $user->getFullName());
        $this->assertSame($initials, $user->getInitials());
    }
}
