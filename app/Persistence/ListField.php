<?php

declare(strict_types=1);

namespace App\Persistence;

use App\Console\EditListDialog;
use App\Context;

class ListField extends Field
{
    public function __construct(
        public readonly string $table,
        public readonly string $name,
        public readonly Field $type,
        public readonly string $label,
        public readonly bool $sortable = false,
        protected array $validators = [],
        public readonly ?string $description = null,
    ) {
    }

    /**
     * Get value for an empty field state.
     *
     * @return mixed
     */
    public function getEmptyValue(): mixed
    {
        return [];
    }

    public function ask(Context $context, mixed $defaultAnswer = null): mixed
    {
        return (new EditListDialog(
            $context,
            $context->db->getTable($this->table)
        ))->render($this, $defaultAnswer);
    }

    /**
     * Convert a value to a printable representation.
     *
     * @param mixed $value
     * @return string
     */
    public function valueToString(mixed $value): string
    {
        return implode(', ', array_map(fn ($element) => $this->type->valueToString($element), $value));
    }
}
