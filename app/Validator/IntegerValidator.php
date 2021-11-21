<?php

namespace App\Validator;

/**
 * Validate for an interger.
 */
class IntegerValidator extends AbstractValidator
{
    protected function isValid($value)
    {
        if (!preg_match('/^-?[0-9]+$/', $value)) {
            throw new \Exception('Must be an integer');
        }

        return (int) $value;
    }
}
