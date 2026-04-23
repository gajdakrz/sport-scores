<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Helper\ValidationError;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;

class ValidationErrorTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeViolation(
        string $message,
        string $propertyPath,
        mixed $invalidValue = null,
    ): ConstraintViolationInterface&MockObject {
        $violation = $this->createMock(ConstraintViolationInterface::class);
        $violation->method('getMessage')->willReturn($message);
        $violation->method('getPropertyPath')->willReturn($propertyPath);
        $violation->method('getInvalidValue')->willReturn($invalidValue);

        return $violation;
    }

    // -------------------------------------------------------------------------
    // Konstruktor – message
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Sets message from violation getMessage()')]
    public function constructorSetsMessage(): void
    {
        $error = new ValidationError($this->makeViolation('Too short', 'name'));

        self::assertSame('Too short', $error->message);
    }

    // -------------------------------------------------------------------------
    // setField – prosta ścieżka (bez nawiasów)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Sets field to the full property path when path contains no brackets')]
    public function simplePropertyPathSetsField(): void
    {
        $error = new ValidationError($this->makeViolation('err', 'username'));

        self::assertSame('username', $error->field);
    }

    #[Test]
    #[TestDox('Leaves arrayPropertyValues empty when property path has no brackets')]
    public function simplePropertyPathDoesNotPopulateArrayPropertyValues(): void
    {
        $error = new ValidationError($this->makeViolation('err', 'username'));

        self::assertEmpty($error->arrayPropertyValues);
    }

    // -------------------------------------------------------------------------
    // setField – ścieżka z nawiasem (element kolekcji)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Sets field to the part of property path before the first bracket')]
    public function bracketedPropertyPathExtractsFieldBeforeBracket(): void
    {
        $error = new ValidationError($this->makeViolation('err', 'tags[0]', 'php'));

        self::assertSame('tags', $error->field);
    }

    #[Test]
    #[TestDox('Appends the invalid value to arrayPropertyValues when path contains a bracket')]
    public function bracketedPropertyPathAddsInvalidValueToArray(): void
    {
        $error = new ValidationError($this->makeViolation('err', 'tags[0]', 'php'));

        self::assertSame(['php'], $error->arrayPropertyValues);
    }

    #[Test]
    #[TestDox('Stores null as invalid value when violation reports null for a bracketed path')]
    public function bracketedPropertyPathStoresNullInvalidValue(): void
    {
        $error = new ValidationError($this->makeViolation('err', 'items[0]', null));

        self::assertSame([null], $error->arrayPropertyValues);
    }

    // -------------------------------------------------------------------------
    // isArrayPropertyValuesNotEmpty
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Returns false when arrayPropertyValues is empty')]
    public function isArrayPropertyValuesNotEmptyReturnsFalseWhenEmpty(): void
    {
        $error = new ValidationError($this->makeViolation('err', 'name'));

        self::assertFalse($error->isArrayPropertyValuesNotEmpty());
    }

    #[Test]
    #[TestDox('Returns true when arrayPropertyValues contains at least one entry')]
    public function isArrayPropertyValuesNotEmptyReturnsTrueWhenNotEmpty(): void
    {
        $error = new ValidationError($this->makeViolation('err', 'tags[0]', 'php'));

        self::assertTrue($error->isArrayPropertyValuesNotEmpty());
    }

    // -------------------------------------------------------------------------
    // isErrorToMerge
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Returns true when both errors share the same message, field, and non-empty arrayPropertyValues')]
    public function isErrorToMergeReturnsTrueForMatchingErrors(): void
    {
        $errorA = new ValidationError($this->makeViolation('Too short', 'tags[0]', 'php'));
        $errorB = new ValidationError($this->makeViolation('Too short', 'tags[1]', 'js'));

        self::assertTrue($errorA->isErrorToMerge($errorB));
    }

    #[Test]
    #[TestDox('Returns false when messages differ')]
    public function isErrorToMergeReturnsFalseWhenMessagesDiffer(): void
    {
        $errorA = new ValidationError($this->makeViolation('Too short', 'tags[0]', 'php'));
        $errorB = new ValidationError($this->makeViolation('Too long', 'tags[1]', 'js'));

        self::assertFalse($errorA->isErrorToMerge($errorB));
    }

    #[Test]
    #[TestDox('Returns false when fields differ')]
    public function isErrorToMergeReturnsFalseWhenFieldsDiffer(): void
    {
        $errorA = new ValidationError($this->makeViolation('err', 'tags[0]', 'php'));
        $errorB = new ValidationError($this->makeViolation('err', 'names[0]', 'js'));

        self::assertFalse($errorA->isErrorToMerge($errorB));
    }

    #[Test]
    #[TestDox('Returns false when the current error has empty arrayPropertyValues')]
    public function isErrorToMergeReturnsFalseWhenCurrentArrayValuesEmpty(): void
    {
        $errorA = new ValidationError($this->makeViolation('err', 'name'));
        $errorB = new ValidationError($this->makeViolation('err', 'tags[0]', 'php'));

        self::assertFalse($errorA->isErrorToMerge($errorB));
    }

    #[Test]
    #[TestDox('Returns false when the passed error has empty arrayPropertyValues')]
    public function isErrorToMergeReturnsFalseWhenPassedErrorArrayValuesEmpty(): void
    {
        $errorA = new ValidationError($this->makeViolation('err', 'tags[0]', 'php'));
        $errorB = new ValidationError($this->makeViolation('err', 'name'));

        self::assertFalse($errorA->isErrorToMerge($errorB));
    }

    // -------------------------------------------------------------------------
    // mergeErrors
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Merges arrayPropertyValues from both errors into the current one')]
    public function mergeErrorsCombinesArrayPropertyValues(): void
    {
        $errorA = new ValidationError($this->makeViolation('err', 'tags[0]', 'php'));
        $errorB = new ValidationError($this->makeViolation('err', 'tags[1]', 'js'));

        $errorA->mergeErrors($errorB);

        self::assertSame(['php', 'js'], $errorA->arrayPropertyValues);
    }

    #[Test]
    #[TestDox('Returns the same instance after merging (fluent interface)')]
    public function mergeErrorsReturnsSelf(): void
    {
        $errorA = new ValidationError($this->makeViolation('err', 'tags[0]', 'php'));
        $errorB = new ValidationError($this->makeViolation('err', 'tags[1]', 'js'));

        $result = $errorA->mergeErrors($errorB);

        self::assertSame($errorA, $result);
    }

    #[Test]
    #[TestDox('Does not modify the passed error after merging')]
    public function mergeErrorsDoesNotMutatePassedError(): void
    {
        $errorA = new ValidationError($this->makeViolation('err', 'tags[0]', 'php'));
        $errorB = new ValidationError($this->makeViolation('err', 'tags[1]', 'js'));

        $errorA->mergeErrors($errorB);

        self::assertSame(['js'], $errorB->arrayPropertyValues);
    }

    // -------------------------------------------------------------------------
    // getErrorAsArray – field (snake_case)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Returns field converted to snake_case in getErrorAsArray')]
    public function getErrorAsArrayConvertsFieldToSnakeCase(): void
    {
        $error = new ValidationError($this->makeViolation('err', 'firstName'));

        self::assertSame('first_name', $error->getErrorAsArray()['field']);
    }

    #[Test]
    #[TestDox('Leaves an already snake_case field unchanged in getErrorAsArray')]
    public function getErrorAsArrayKeepsSnakeCaseFieldUnchanged(): void
    {
        $error = new ValidationError($this->makeViolation('err', 'username'));

        self::assertSame('username', $error->getErrorAsArray()['field']);
    }

    // -------------------------------------------------------------------------
    // getErrorAsArray – message (bez arrayPropertyValues)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Returns plain message in getErrorAsArray when arrayPropertyValues is empty')]
    public function getErrorAsArrayReturnsPlainMessageWhenNoArrayValues(): void
    {
        $error = new ValidationError($this->makeViolation('Too short', 'name'));

        self::assertSame('Too short', $error->getErrorAsArray()['message']);
    }

    // -------------------------------------------------------------------------
    // getErrorAsArray – message (z arrayPropertyValues)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Appends formatted array values to message when arrayPropertyValues is not empty')]
    public function getErrorAsArrayAppendsArrayValuesToMessage(): void
    {
        $error = new ValidationError($this->makeViolation('Invalid', 'tags[0]', 'php'));

        self::assertSame('Invalid Values = [php]', $error->getErrorAsArray()['message']);
    }

    #[Test]
    #[TestDox('Appends merged array values to message after mergeErrors')]
    public function getErrorAsArrayAppendsAllValuesAfterMerge(): void
    {
        $errorA = new ValidationError($this->makeViolation('Invalid', 'tags[0]', 'php'));
        $errorB = new ValidationError($this->makeViolation('Invalid', 'tags[1]', 'js'));

        $errorA->mergeErrors($errorB);

        self::assertSame('Invalid Values = [php, js]', $errorA->getErrorAsArray()['message']);
    }

    // -------------------------------------------------------------------------
    // getErrorAsArray – struktura zwracanej tablicy
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Returns array with exactly the keys "message" and "field"')]
    public function getErrorAsArrayReturnsCorrectKeys(): void
    {
        $error = new ValidationError($this->makeViolation('err', 'name'));

        self::assertSame(['message', 'field'], array_keys($error->getErrorAsArray()));
    }
}
