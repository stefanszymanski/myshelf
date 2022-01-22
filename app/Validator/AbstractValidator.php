<?php

namespace App\Validator;

abstract class AbstractValidator implements Validator
{
    /**
     * Whether an empty value should pass the validation.
     *
     * @var bool
     */
    protected bool $allowEmpty = true;

    /**
     * Validate the given value.
     *
     * @param mixed $value The value to validate
     * @return mixed The given value with or without normalization
     * @throws ValidationException if the validation fails.
     */
    public function validate(mixed $value): mixed
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            if ($this->allowEmpty) {
                return $value;
            } else {
                throw new ValidationException('Must not be empty');
            }
        }
        return $this->isValid($value);
    }

    /**
     * Perform the actual validation.
     *
     * @param mixed $value The value to validate
     * @return mixed The given value with or without normalization
     */
    protected function isValid(mixed $value): mixed
    {
        return $value;
    }

    /**
     * @see self::validate()
     */
    public function __invoke(mixed $value): mixed
    {
        return $this->validate($value);
    }
}
