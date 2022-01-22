<?php

declare(strict_types=1);

namespace App\Persistence\Data;

use App\Console\EditStructDialog;
use App\Context;
use App\Utility\RecordUtility;
use App\Validator\Validator;
use Illuminate\Container\Container;

class StructField extends AbstractField implements ContainerFieldContract
{
    /**
     * @param array<string,Field> $fields
     * @param Validator $validator
     * @param string $label
     * @param \Closure $formatter
     */
    public function __construct(
        protected readonly array $fields,
        protected readonly Validator $validator,
        protected readonly string $label,
        protected readonly ?\Closure $formatter = null,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function formatValue(mixed $value): string
    {
        if ($value === null || $value === []) {
            return '';
        }
        if (!is_array($value)) {
            return sprintf('<bg=red>%s</>', RecordUtility::convertToString($value));
        }
        if ($this->formatter) {
            return ($this->formatter)($value, $this->fields);
        }
        $value = array_filter($value);
        return implode("\n", array_map(
            (function ($key, $value) {
                $field = $this->fields[$key] ?? null;
                return $field
                    ? sprintf('<info>%s:</info> %s', $field->getLabel(), $field->formatValue($value))
                    : "<bg=red>$key: $value</>";
            })->bindTo($this),
            array_keys($value),
            array_values($value)
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function askForValue(Context $context, mixed $defaultValue): mixed
    {
        if (!is_array($defaultValue)) {
            $defaultValue = [];
        }
        return (new EditStructDialog($context, $this))->render($defaultValue)
            ?? $defaultValue;
    }

    /**
     * {@inheritDoc}
     */
    public function getSubFields(): array
    {
        return $this->fields;
    }

    public function getSubField(string $fieldName): Field
    {
        list($nextFieldName, $subFieldName) = array_pad(explode('.', $fieldName, 2), 2, null);
        $field = $this->fields[$nextFieldName];
        if ($subFieldName) {
            if (!($field instanceof Container)) {

            }
        }
    }

    /**
     * Get a sub field by its name.
     *
     * @param string $fieldName
     * @return Field
     * @throws \InvalidArgumentException if the field doesn't exist.
     */
    public function getField(string $fieldName): Field
    {
        if (str_contains($fieldName, '.')) {
            list($rootFieldName, $subFieldName) = explode('.', $fieldName, 2);
            if (!isset($this->fields[$rootFieldName])) {
                throw new \InvalidArgumentException("Invalid data field name: $fieldName");
            }
            $rootField = $this->fields[$rootFieldName];
            $field = $rootField->getField($subFieldName);
        } else {
            if (!isset($this->fields[$fieldName])) {
                throw new \InvalidArgumentException("Invalid data field name: $fieldName");
            }
            $field = $this->fields[$fieldName];
        }
        return $field;
    }
}
