<?php

namespace App\Validator;

/**
 * Validate for an interger.
 */
class IntegerValidator extends AbstractValidator
{
    public function __construct(protected ?int $min = null, protected ?int $max = null)
    {
    }

    protected function isValid(mixed $value): mixed
    {
        if (!is_int($value) && !is_string($value)) {
            throw new \InvalidArgumentException('Argument $value must be an int or a string');
        }
        if (!preg_match('/^-?[0-9]+$/', (string)$value)) {
            throw new ValidationException('Must be an integer');
        }

        $value = (int) $value;

        if ($this->min !== null && $value < $this->min) {
            throw new ValidationException(sprintf('Must not be less than %d', $this->min));
        }
        if ($this->max !== null && $value > $this->max) {
            throw new ValidationException(sprintf('Must not be greater than %d', $this->max));
        }

        return $value;
    }
}
