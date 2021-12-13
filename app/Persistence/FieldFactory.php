<?php

declare(strict_types=1);

namespace App\Persistence;

use App\Validator\NotEmptyValidator;
use App\Validator\OptionsValidator;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class FieldFactory
{
    /**
     * Create a table field.
     *
     * @param string $tableName Name of the table that contains the field
     * @param string $fieldName Name of the field
     * @param string $label Label for the UI
     * @param bool $required Whether the field must have a non-empty value
     * @param callable(string):Question|null $question Callable that takes a default value and returns a Question object
     * @param array<callable(mixed):mixed>|callable(mixed):mixed $validators One or more callables that take a field
     *                                                                       value and throw an exception when the
     *                                                                       validation fails, return the field value
     * @param callable(mixed):string|null $formatter Callable that takes a field value and returns a string representation
     * @param string|null $description Description for the UI
     * @return Field
     */
    public function createField(
        string $tableName,
        string $fieldName,
        string $label,
        bool $required = false,
        ?callable $question = null,
        array|callable $validators = [],
        ?callable $formatter = null,
        ?string $description = null
    ): Field {
        // TODO support multivalue fields
        if (!is_array($validators)) {
            $validators = [$validators];
        }
        if ($required) {
            $validators[] = fn () => new NotEmptyValidator;
        }
        return new Field(
            table: $tableName,
            name: $fieldName,
            label: $label,
            description: $description,
            question: $question,
            validators: $validators,
            formatter: $formatter,
        );
    }

    /**
     * Create a table field that references one or more records of another (or the same) table.
     *
     * @param string $tableName Name of the table that contains the field
     * @param string $fieldName Name of the field
     * @param string $foreignTable Name of the referenced table
     * @param string $label Label for the UI
     * @param bool $required Whether the field must have a non-empty value
     * @param callable(mixed):string|null $formatter Callable that takes a field value and returns a string representation
     * @param string|null $description Description for the UI
     * @param bool $multiple Whether the field holds multiple references
     * @param bool $sortable Whether the references are sortable, only supported if `$multiple` is `true`
     * @param ?string $elementLabel Label for a single reference, only supported if `$multiple` is `true`
     * @return Field
     */
    public function createReferenceField(
        string $tableName,
        string $fieldName,
        string $foreignTable,
        string $label,
        bool $required = false,
        ?callable $formatter = null,
        ?string $description = null,
        bool $multiple = false,
        bool $sortable = false,
        ?string $elementLabel = null
    ): Field {
        // Check for arguments that are only supported on a multivalue field.
        if (!$multiple && $sortable) {
            throw new \InvalidArgumentException('Argument $sortable must be false if $multiple is false');
        }
        if (!$multiple && $elementLabel) {
            throw new \InvalidArgumentException('Argument $elementLabel must not be set if $multiple is false');
        }
        // Create a validator if the field is required.
        $validators = $required ?
            [fn () => new NotEmptyValidator]
            : [];
        // Create the ReferenceField.
        $field = new ReferenceField(
            table: $tableName,
            name: $fieldName,
            foreignTable: $foreignTable,
            label: $elementLabel ?? $label,
            description: $description,
            formatter: $formatter,
            validators: $validators,
        );
        // If it's a multivalue field, wrap the ReferenceField with a ListField.
        if ($multiple) {
            $field = new ListField(
                table: $tableName,
                name: $fieldName,
                type: $field,
                label: $label,
                validators: $validators,
                sortable: $sortable,
            );
        }
        return $field;
    }

    /**
     * Create a table field that contains subfields.
     *
     * @param string $tableName Name of the table that contains the field
     * @param string $fieldName Name of the field
     * @param string $label Label for the UI
     * @param array<callable(mixed):mixed>|callable(mixed):mixed $validators One or more callables that take a field
     *                                                                       value and throw an exception when the
     *                                                                       validation fails, return the field value
     * @param callable(mixed):string|null $formatter Callable that takes a field value and returns a string representation
     * @param string|null $description Description for the UI
     * @return StructField
     */
    public function createStructField(
        string $tableName,
        string $fieldName,
        string $label,
        array|callable $validators = [],
        ?callable $formatter = null,
        ?string $description = null,
    ): StructField {
        // TODO support multivalue fields
        if (!is_array($validators)) {
            $validators = [$validators];
        }
        return new StructField(
            table: $tableName,
            name: $fieldName,
            label: $label,
            description: $description,
            validators: $validators,
            formatter: $formatter,
        );
    }

    /**
     * Create a table field with a fixed set of values.
     *
     * @param string $tableName Name of the table that contains the field
     * @param string $fieldName Field name
     * @param string $label
     * @param array<mixed,string> $options Keys are stored, values are labels for the UI
     * @param bool $required Whether the field must have a non-empty value
     * @param string|null $description
     * @return Field
     */
    public function createSelectField(
        string $tableName,
        string $fieldName,
        string $label,
        array $options,
        bool $required = false,
        ?string $description = null
    ) {
        $question = function ($defaultValue) use ($options, $label) {
            $value = array_search($defaultValue, array_keys($options)) ?: null;
            return new ChoiceQuestion(sprintf('Select a %s', $label), array_values($options), $value);
        };
        $validators = [
            fn () => new OptionsValidator(array_keys($options))
        ];
        $formatter = fn ($value = null) => match (true) {
            empty($value) => '',
            array_key_exists($value, $options) => $options[$value],
            default => sprintf('<bg=red>invalid: %s</>', $value)
        };
        return $this->createField(
            $tableName,
            $fieldName,
            $label,
            $required,
            $question,
            $validators,
            $formatter,
            $description,
        );
    }
}
