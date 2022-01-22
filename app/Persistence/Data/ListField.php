<?php

declare(strict_types=1);

namespace App\Persistence\Data;

use App\Console\EditListDialog;
use App\Context;
use App\Validator\Validator;

class ListField extends AbstractField implements MultivalueFieldContract
{
    /**
     * @param Field $field
     * @param bool $sortable
     * @param Validator $validator
     */
    public function __construct(
        protected readonly Field $field,
        public readonly bool $sortable,
        protected readonly Validator $validator,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return $this->field->getLabel();
    }

    /**
     * {@inheritDoc}
     */
    public function getField(): Field
    {
        return $this->field;
    }

    /**
     * {@inheritDoc}
     */
    public function formatValue(mixed $value): string
    {
        return collect($value)
            ->map($this->field->formatValue(...))
            ->implode("\n");
    }

    /**
     * {@inheritDoc}
     */
    public function askForValue(Context $context, mixed $defaultValue): mixed
    {
        return (new EditListDialog($context))->render($this, $defaultValue ?? []);
    }

    /**
     * {@inheritDoc}
     */
    public function getEmptyValue(): mixed
    {
        return [];
    }
}
