<?php

declare(strict_types=1);

namespace App\Validator;

class ConjunctionValidator implements Validator
{
    public function __construct(protected callable ...$validators)
    {
    }

    /**
     * Validate the given value.
     *
     * @param mixed $value The value to validate
     * @return mixed The given value with or without normalization
     * @throws ValidationException if the validation fails.
     */
    public function validate(mixed $value): mixed
    {
        return array_reduce(
            $this->validators,
            fn (mixed $carry, callable $validator) => call_user_func($validator, $carry),
            $value
        );
    }

    /**
     * @see self::validate()
     */
    public function __invoke(mixed $value): mixed
    {
        return $this->validate($value);
    }
}
