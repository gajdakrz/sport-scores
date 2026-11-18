<?php

declare(strict_types=1);

namespace App\Tests\Unit\Helper;

use App\Helper\StringNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

final class StringNormalizerTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function whitespaceProvider(): array
    {
        return [
            'already normalized'     => ['Real Madrid', 'Real Madrid'],
            'leading and trailing'   => ['  Real Madrid  ', 'Real Madrid'],
            'repeated inner spaces'  => ['Real    Madrid', 'Real Madrid'],
            'tabs and new lines'     => ["Real\t\n Madrid", 'Real Madrid'],
            'non-breaking spaces'    => ["\u{00A0}Real\u{00A0}\u{00A0}Madrid", 'Real Madrid'],
            'unicode letters intact' => ['  Łódzki   KS ', 'Łódzki KS'],
            'only whitespace'        => ["  \t ", ''],
        ];
    }

    #[Test]
    #[DataProvider('whitespaceProvider')]
    #[TestDox('Normalizes $_dataName')]
    public function normalizesWhitespace(string $input, string $expected): void
    {
        $this->assertSame($expected, StringNormalizer::normalizeWhitespace($input));
    }
}
