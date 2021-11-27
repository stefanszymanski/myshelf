<?php

namespace App\Validator;

/**
 * Validate for an interger.
 */
class IntegerValidator extends AbstractValidator
{
    protected function isValid(mixed $value): mixed
    {
        if (!is_int($value) && !is_string($value)) {
            throw new \InvalidArgumentException('Argument $value must be an int or a string');
        }
        if (!preg_match('/^-?[0-9]+$/', (string)$value)) {
            throw new \Exception('Must be an integer');
        }

        return (int) $value;
    }
}
