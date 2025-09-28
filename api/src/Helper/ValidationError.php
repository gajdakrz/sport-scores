<?php

declare(strict_types=1);

namespace App\Helper;

use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Validator\ConstraintViolationInterface;

class ValidationError
{
    public string $message;
    public string $field;
    /**
     * @var mixed[]
     */
    public array $arrayPropertyValues = [];

    public function __construct(ConstraintViolationInterface $violation)
    {
        $this->message = (string)$violation->getMessage();
        $this->setField($violation);
    }

    public function isArrayPropertyValuesNotEmpty(): bool
    {
        return 0 < count($this->arrayPropertyValues);
    }

    public function isErrorToMerge(ValidationError $error): bool
    {
        return $this->message === $error->message
            && $this->field === $error->field
            && $this->isArrayPropertyValuesNotEmpty()
            && $error->isArrayPropertyValuesNotEmpty();
    }

    public function mergeErrors(ValidationError $error): self
    {
        $this->arrayPropertyValues = array_merge($this->arrayPropertyValues, $error->arrayPropertyValues);

        return $this;
    }

    /**
     * @return array{
     *     message: string,
     *     field: string
     * }
     */
    public function getErrorAsArray(): array
    {
        return [
            'message' => $this->getErrorMessage(),
            'field' => $this->getFieldAsCamelCase()
        ];
    }

    private function getFieldAsCamelCase(): string
    {
        return (new CamelCaseToSnakeCaseNameConverter())->normalize($this->field);
    }

    private function getErrorMessage(): string
    {
        $message = $this->message;
        if ($this->isArrayPropertyValuesNotEmpty()) {
            $message .= ' Values = ' . (new ArrayConverter())->toStringOfValues($this->arrayPropertyValues);
        }

        return $message;
    }

    private function setField(ConstraintViolationInterface $violation): void
    {
        $explodedPropertyPath = explode('[', $violation->getPropertyPath());
        $this->field = $explodedPropertyPath[0];

        if (1 < count($explodedPropertyPath)) {
            $this->arrayPropertyValues[] = $violation->getInvalidValue();
        }
    }
}
