<?php

namespace App\Validator;

abstract class AbstractValidator
{
    protected bool $allowEmpty = true;

    public function validate($value)
    {
        if (empty($value)) {
            if ($this->allowEmpty) {
                return $value;
            } else {
                throw new ValidationException('Must not be empty');
            }
        }
        return $this->isValid($value);
    }

    protected function isValid($value)
    {
        return $value;
    }

    public function __invoke($value)
    {
        return $this->validate($value);
    }
}
