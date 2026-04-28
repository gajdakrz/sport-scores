<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Helper\ArrayConverter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

class ArrayConverterTest extends TestCase
{
    private ArrayConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new ArrayConverter();
    }

    /**
     * @return array<string, array{mixed, string}>
     */
    public static function scalarProvider(): array
    {
        return [
            'string'        => ['hello',   '[hello]'],
            'empty string'  => ['',        '[]'],
            'int zero'      => [0,          '[0]'],
            'int positive'  => [42,         '[42]'],
            'int negative'  => [-7,         '[-7]'],
            'float'         => [3.14,       '[3.14]'],
            'float zero'    => [0.0,        '[0]'],
            'bool true'     => [true,       '[true]'],
            'bool false'    => [false,      '[false]'],
            'null'          => [null,       '[null]'],
        ];
    }

    // -------------------------------------------------------------------------
    // Pusta tablica
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Returns "[]" for an empty array')]
    public function emptyArrayReturnsEmptyBrackets(): void
    {
        self::assertSame('[]', $this->converter->toStringOfValues([]));
    }

    // -------------------------------------------------------------------------
    // Typy skalarne
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Converts scalar value "$value" to string representation')]
    #[DataProvider('scalarProvider')]
    public function scalarValuesAreConvertedCorrectly(mixed $value, string $expected): void
    {
        self::assertSame($expected, $this->converter->toStringOfValues([$value]));
    }

    // -------------------------------------------------------------------------
    // Domyślny separator
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Joins multiple scalar values with the default separator ", "')]
    public function multipleScalarsAreJoinedWithDefaultSeparator(): void
    {
        self::assertSame('[1, 2, 3]', $this->converter->toStringOfValues([1, 2, 3]));
    }

    #[Test]
    #[TestDox('Converts mixed scalar types (string, int, bool, null) in a single array')]
    public function mixedScalarTypesAreJoinedCorrectly(): void
    {
        self::assertSame('[hello, 42, true, null]', $this->converter->toStringOfValues(['hello', 42, true, null]));
    }

    // -------------------------------------------------------------------------
    // Własny separator
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Uses the provided custom separator instead of the default one')]
    public function customSeparatorIsRespected(): void
    {
        self::assertSame('[1|2|3]', $this->converter->toStringOfValues([1, 2, 3], '|'));
    }

    #[Test]
    #[TestDox('Propagates the custom separator recursively into nested arrays')]
    public function customSeparatorIsPassedToNestedArrays(): void
    {
        self::assertSame('[[1|2]|3]', $this->converter->toStringOfValues([[1, 2], 3], '|'));
    }

    // -------------------------------------------------------------------------
    // Zagnieżdżone tablice
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Wraps a nested array in its own pair of brackets')]
    public function nestedArrayIsWrappedInBrackets(): void
    {
        self::assertSame('[[1, 2]]', $this->converter->toStringOfValues([[1, 2]]));
    }

    #[Test]
    #[TestDox('Handles deeply nested arrays with multiple levels of brackets')]
    public function deeplyNestedArraysAreHandled(): void
    {
        self::assertSame('[[[1]]]', $this->converter->toStringOfValues([[[1]]]));
    }

    #[Test]
    #[TestDox('Correctly mixes flat scalar values and nested arrays in one result')]
    public function mixedFlatAndNestedValues(): void
    {
        self::assertSame('[a, [1, 2], b]', $this->converter->toStringOfValues(['a', [1, 2], 'b']));
    }

    #[Test]
    #[TestDox('Renders each empty nested array as its own "[]" token')]
    public function emptyNestedArrayProducesEmptyBrackets(): void
    {
        self::assertSame('[[], []]', $this->converter->toStringOfValues([[], []]));
    }

    // -------------------------------------------------------------------------
    // Obiekty (get_object_vars)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Converts an object by extracting and formatting its public properties')]
    public function objectPublicPropertiesAreConverted(): void
    {
        $object = new \stdClass();
        $object->name = 'Jan';
        $object->age  = 30;

        self::assertSame('[[Jan, 30]]', $this->converter->toStringOfValues([$object]));
    }

    #[Test]
    #[TestDox('Renders an object with no public properties as "[]"')]
    public function objectWithNoPublicPropertiesProducesEmptyBrackets(): void
    {
        self::assertSame('[[]]', $this->converter->toStringOfValues([new \stdClass()]));
    }

    #[Test]
    #[TestDox('Recursively converts an object property that holds an array')]
    public function objectWithNestedArrayProperty(): void
    {
        $object = new \stdClass();
        $object->tags = ['php', 'symfony'];

        self::assertSame('[[[php, symfony]]]', $this->converter->toStringOfValues([$object]));
    }

    // -------------------------------------------------------------------------
    // Typ nieznanego (resource itp.) – gałąź default
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Falls back to "[unsupported]" marker for resource values')]
    public function resourceValueFallsBackToUnsupportedMarker(): void
    {
        $resource = fopen('php://memory', 'r');
        self::assertIsResource($resource);
        self::assertSame('[[unsupported]]', $this->converter->toStringOfValues([$resource]));
        fclose($resource);
    }

    // -------------------------------------------------------------------------
    // Jednoelementowa tablica
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Wraps a single element in brackets without any separator')]
    public function singleElementArrayHasNoBracketsAroundSeparator(): void
    {
        self::assertSame('[only]', $this->converter->toStringOfValues(['only']));
    }
}
