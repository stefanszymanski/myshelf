<?php

namespace App\Validator;

abstract class AbstractValidator
{
    protected bool $allowEmpty = true;

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

    protected function isValid(mixed $value): mixed
    {
        return $value;
    }

    public function __invoke(mixed $value): mixed
    {
        return $this->validate($value);
    }
}
