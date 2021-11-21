<?php

namespace App\Validator;

/**
 * Validate that the value is not empty.
 */
class NotEmptyValidator extends AbstractValidator
{
    protected bool $allowEmpty = false;
}
