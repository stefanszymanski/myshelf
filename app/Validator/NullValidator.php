<?php

declare(strict_types=1);

namespace App\Validator;

class NullValidator implements Validator
{
    /**
     * Validate the given value.
     *
     * @param mixed $value The value to validate
     * @return mixed The given value
     */
    public function validate(mixed $value): mixed
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
