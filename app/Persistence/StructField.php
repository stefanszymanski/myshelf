<?php

declare(strict_types=1);

namespace App\Persistence;

use App\Console\EditStructDialog;
use App\Context;
use App\Utility\RecordUtility;
use Symfony\Component\Console\Question\Question;

class StructField extends Field
{
    /**
     * @var array<string,Field>
     */
    protected array $fields = [];

    /**
     * @param string $table Name of the table
     * @param string $name Name of the field
     * @param string $label Label for the UI
     * @param string|null $description Description for the UI
     * @param array<callable(mixed):mixed> $validators Callables that take a field value and throw an exception when the
     *                                                 validation fails, return the field value
     * @param \Closure(mixed):string|null $formatter Callable that takes a field value and returns a string representation
     */
    public function __construct(
        public readonly string $table,
        public readonly string $name,
        public readonly string $label,
        public readonly ?string $description = null,
        protected array $validators = [],
        protected ?\Closure $formatter = null,
    ) {
    }

    /**
     * Get the fields of the struct.
     *
     * @return array<Field>
     */
    public function getFields(): array
    {
        return array_values($this->fields);
    }

    /**
     * Ask the user for a value.
     *
     * @param Context $context
     * @param mixed $defaultAnswer
     * @return mixed
     */
    public function ask(Context $context, mixed $defaultAnswer = []): mixed
    {
        return (new EditStructDialog($context, $this))->render($defaultAnswer ?? []) ?? $defaultAnswer ?? null;
    }

    /**
     * Check whether a value is allowed by the field.
     *
     * @param mixed $value
     * @return bool
     */
    public function validate(mixed $value): bool
    {
        if (!is_array($value)) {
            $value = [];
        }
        foreach ($this->fields as $field) {
            if (!$field->validate($value[$field->name] ?? null)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Convert a value to a printable representation.
     *
     * @param mixed $value
     * @return string
     */
    public function valueToString(mixed $value): string
    {
        if ($this->formatter) {
            return call_user_func($this->formatter, $value);
        }
        if ($value === null || $value === []) {
            return '';
        }
        if (!is_array($value)) {
            return sprintf('<bg=red>%s</>', RecordUtility::convertToString($value));
        }
        $value = array_filter($value);
        return implode("\n", array_map(
            (function($key, $value) {
                $field = $this->fields[$key] ?? null;
                return $field
                    ? sprintf('<info>%s:</info> %s', $field->label, $field->valueToString($value))
                    : "<bg=red>$key: $value</>";
            })->bindTo($this),
            array_keys($value),
            array_values($value)
        ));
    }

    /**
     * Add a field to the struct.
     *
     * @param string $name Name of the field
     * @param string $label Label for the UI
     * @param bool $required Whether the field must have a non-empty value
     * @param callable(string):Question|null $question Callable that takes a default value and returns a Question object
     * @param array<callable(mixed):mixed>|callable(mixed):mixed $validators One or more callables that take a field
     *                                                                       value and throw an exception when the
     *                                                                       validation fails, return the field value
     * @param callable(mixed):string|null $formatter Callable that takes a field value and returns a string representation
     * @param string|null $description Description for the UI
     * @return self
     */
    public function addField(
        string $name,
        string $label,
        bool $required = false,
        ?callable $question = null,
        array|callable $validators = [],
        ?callable $formatter = null,
        ?string $description = null
    ): self {
        $fieldFactory = new FieldFactory;
        $this->fields[$name] = $fieldFactory->createField(
            $this->table,
            $name,
            $label,
            $required,
            $question,
            $validators,
            $formatter,
            $description
        );
        return $this;
    }
}
