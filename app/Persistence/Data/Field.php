<?php

declare(strict_types=1);

namespace App\Persistence\Data;

use App\Context;

interface Field
{
    /**
     * Format a field value as string.
     *
     * @param mixed $value
     * @return string
     */
    public function formatValue(mixed $value): string;

    /**
     * Ask the user for a field value.
     *
     * @param Context $context
     * @param mixed $defaultValue
     * @return mixed
     */
    public function askForValue(Context $context, mixed $defaultValue): mixed;

    /**
     * Validate a field value.
     *
     * @param mixed $value
     * @return bool
     */
    public function validateValue(mixed $value): bool;

    /**
     * Get an empty field value.
     *
     * @return mixed
     */
    public function getEmptyValue(): mixed;

    /**
     * Get the field label.
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Get references to other tables defined by this field.
     *
     * @return References
     */
    public function getReferences(): References;
}
