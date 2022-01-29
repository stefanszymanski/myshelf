<?php

declare(strict_types=1);

namespace App\Validator;

class ConjunctionValidator implements Validator
{
    /**
     * @param array<Validator> $validators
     */
    public function __construct(protected readonly array $validators)
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
            fn (mixed $carry, Validator $validator) => $validator->validate($carry),
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
