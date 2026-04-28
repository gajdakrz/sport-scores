<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Helper\ValidationHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class ValidationHelperTest extends TestCase
{
    private ValidationHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new ValidationHelper();
    }

    // -------------------------------------------------------------------------
    // getValidationErrorsAsArray – stan początkowy
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Returns empty array before any violations are processed')]
    public function returnsEmptyArrayBeforeProcessing(): void
    {
        self::assertSame([], $this->helper->getValidationErrorsAsArray());
    }

    // -------------------------------------------------------------------------
    // prepareValidationErrors – brak naruszeń
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Produces no errors when exception carries an empty violation list')]
    public function noErrorsWhenViolationListIsEmpty(): void
    {
        $this->helper->prepareValidationErrors($this->makeException());

        self::assertSame([], $this->helper->getValidationErrorsAsArray());
    }

    // -------------------------------------------------------------------------
    // prepareValidationErrors – pojedyncze naruszenie (prosta ścieżka)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Adds one error entry for a single simple-path violation')]
    public function singleSimpleViolationProducesOneError(): void
    {
        $exception = $this->makeException(
            $this->makeViolation('Too short', 'name'),
        );

        $this->helper->prepareValidationErrors($exception);

        self::assertCount(1, $this->helper->getValidationErrorsAsArray());
    }

    #[Test]
    #[TestDox('Maps single violation to correct message and field')]
    public function singleViolationIsMappedToMessageAndField(): void
    {
        $exception = $this->makeException(
            $this->makeViolation('Too short', 'name'),
        );

        $this->helper->prepareValidationErrors($exception);

        self::assertSame(
            [['message' => 'Too short', 'field' => 'name']],
            $this->helper->getValidationErrorsAsArray(),
        );
    }

    // -------------------------------------------------------------------------
    // prepareValidationErrors – dwa niezależne naruszenia
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Adds two separate errors for violations with different messages')]
    public function twoDifferentMessagesProduceTwoErrors(): void
    {
        $exception = $this->makeException(
            $this->makeViolation('Too short', 'name'),
            $this->makeViolation('Invalid email', 'email'),
        );

        $this->helper->prepareValidationErrors($exception);

        self::assertCount(2, $this->helper->getValidationErrorsAsArray());
    }

    #[Test]
    #[TestDox('Adds two separate errors for violations with same message but different fields')]
    public function sameMessageDifferentFieldsProduceTwoErrors(): void
    {
        $exception = $this->makeException(
            $this->makeViolation('Required', 'firstName'),
            $this->makeViolation('Required', 'lastName'),
        );

        $this->helper->prepareValidationErrors($exception);

        self::assertCount(2, $this->helper->getValidationErrorsAsArray());
    }

    // -------------------------------------------------------------------------
    // prepareValidationErrors – scalanie (merge) naruszeń tablicowych
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Merges two array-path violations sharing the same message and field into one error')]
    public function twoMergeableViolationsProduceOneError(): void
    {
        $exception = $this->makeException(
            $this->makeViolation('Invalid', 'tags[0]', 'php'),
            $this->makeViolation('Invalid', 'tags[1]', 'js'),
        );

        $this->helper->prepareValidationErrors($exception);

        self::assertCount(1, $this->helper->getValidationErrorsAsArray());
    }

    #[Test]
    #[TestDox('Merged error message contains all invalid values from both violations')]
    public function mergedErrorContainsAllInvalidValues(): void
    {
        $exception = $this->makeException(
            $this->makeViolation('Invalid', 'tags[0]', 'php'),
            $this->makeViolation('Invalid', 'tags[1]', 'js'),
        );

        $this->helper->prepareValidationErrors($exception);

        $errors = $this->helper->getValidationErrorsAsArray();
        self::assertSame('Invalid Values = [php, js]', $errors[0]['message']);
    }

    #[Test]
    #[TestDox('Merges three array-path violations with the same message and field into one error')]
    public function threeMergeableViolationsProduceOneError(): void
    {
        $exception = $this->makeException(
            $this->makeViolation('Invalid', 'tags[0]', 'php'),
            $this->makeViolation('Invalid', 'tags[1]', 'js'),
            $this->makeViolation('Invalid', 'tags[2]', 'go'),
        );

        $this->helper->prepareValidationErrors($exception);

        self::assertCount(1, $this->helper->getValidationErrorsAsArray());
        self::assertSame('Invalid Values = [php, js, go]', $this->helper->getValidationErrorsAsArray()[0]['message']);
    }

    #[Test]
    #[TestDox('Does not merge array-path violations that share a field but differ in message')]
    public function arrayViolationsDifferentMessagesAreNotMerged(): void
    {
        $exception = $this->makeException(
            $this->makeViolation('Too short', 'tags[0]', 'a'),
            $this->makeViolation('Too long', 'tags[1]', 'abcdefghij'),
        );

        $this->helper->prepareValidationErrors($exception);

        self::assertCount(2, $this->helper->getValidationErrorsAsArray());
    }

    // -------------------------------------------------------------------------
    // prepareValidationErrors – mix naruszeń (scalane i niescalane)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Handles a mix of mergeable and independent violations correctly')]
    public function mixedViolationsProduceCorrectErrorCount(): void
    {
        $exception = $this->makeException(
            $this->makeViolation('Invalid', 'tags[0]', 'php'),   // merged
            $this->makeViolation('Invalid', 'tags[1]', 'js'),    // merged with previous
            $this->makeViolation('Required', 'name'),             // independent
        );

        $this->helper->prepareValidationErrors($exception);

        self::assertCount(2, $this->helper->getValidationErrorsAsArray());
    }

    // -------------------------------------------------------------------------
    // getValidationErrorsAsArray – kolejność i struktura
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Preserves the original order of violations in the output array')]
    public function errorsAreReturnedInViolationOrder(): void
    {
        $exception = $this->makeException(
            $this->makeViolation('First error', 'fieldA'),
            $this->makeViolation('Second error', 'fieldB'),
        );

        $this->helper->prepareValidationErrors($exception);

        $errors = $this->helper->getValidationErrorsAsArray();
        self::assertSame('First error', $errors[0]['message']);
        self::assertSame('Second error', $errors[1]['message']);
    }

    #[Test]
    #[TestDox('Each entry in the output contains exactly the keys "message" and "field"')]
    public function eachErrorEntryHasExactlyMessageAndFieldKeys(): void
    {
        $exception = $this->makeException(
            $this->makeViolation('err', 'name'),
        );

        $this->helper->prepareValidationErrors($exception);

        $error = $this->helper->getValidationErrorsAsArray()[0];
        self::assertSame(['message', 'field'], array_keys($error));
    }

    #[Test]
    #[TestDox('Accumulates errors across multiple calls to prepareValidationErrors')]
    public function multipleCallsAccumulateErrors(): void
    {
        $this->helper->prepareValidationErrors(
            $this->makeException($this->makeViolation('err', 'fieldA')),
        );
        $this->helper->prepareValidationErrors(
            $this->makeException($this->makeViolation('err', 'fieldB')),
        );

        self::assertCount(2, $this->helper->getValidationErrorsAsArray());
    }

    // -------------------------------------------------------------------------
    // helpers
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

    private function makeException(ConstraintViolationInterface ...$violations): ValidationFailedException&MockObject
    {
        $list = new ConstraintViolationList($violations);

        $exception = $this->createMock(ValidationFailedException::class);
        $exception->method('getViolations')->willReturn($list);

        return $exception;
    }
}
