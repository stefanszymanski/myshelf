<?php

namespace App\Validator;

/**
 * Validate for being in a fixed list.
 */
class OptionsValidator extends AbstractValidator
{
    /**
     * @param array<int,mixed> $options List of values
     */
    public function __construct(protected array $options)
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function isValid(mixed $value): mixed
    {
        if (!is_int($value) && !ctype_digit($value)) {
            throw new \InvalidArgumentException('Must be an integer or a string containing just an integer');
        }
        $value = (int) $value;
        if ($value < 0 || $value >= sizeof($this->options)) {
            throw new ValidationException('Must be a valid option ' . $value);
        }
        return $this->options[$value];
    }
}
