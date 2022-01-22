<?php

declare(strict_types=1);

namespace App\Persistence\Data;

use App\Utility\RecordUtility;
use App\Validator\ValidationException;
use App\Validator\Validator;

abstract class AbstractField implements Field
{
    /**
     * @param Validator $validator
     * @param string $label
     */
    public function __construct(
        protected readonly Validator $validator,
        protected readonly string $label,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * {@inheritDoc}
     */
    public function formatValue(mixed $value): string
    {
        return RecordUtility::convertToString($value);
    }

    /**
     * {@inheritDoc}
     */
    public function validateValue(mixed $value): bool
    {
        try {
            call_user_func($this->validator, $value);
        } catch (ValidationException $e) {
            return false;
        }
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getEmptyValue(): mixed
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getReferences(): References
    {
        return new References;
    }
}
