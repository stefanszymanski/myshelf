<?php

declare(strict_types=1);

namespace App\Persistence\Data;

use App\Console\RecordSelector;
use App\Context;
use App\Persistence\Database;
use App\Validator\Validator;

class ReferenceField extends AbstractField implements ReferenceFieldContract
{
    /**
     * @param string $targetTable
     * @param Validator $validator
     * @param string $label
     */
    public function __construct(
        protected readonly string $targetTable,
        protected readonly Validator $validator,
        protected readonly string $label,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function formatValue(mixed $value): string
    {
        // TODO move the error handling to AbstractField
        try {
            return app(Database::class)->getTable($this->targetTable)->getRecordTitle($value);
        } catch (\Throwable $e) {
            return sprintf('<error>%s</>', $value);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function askForValue(Context $context, mixed $defaultValue): mixed
    {
        // TODO localization
        $prompt = preg_match('/^[aeiou]/i', $this->getLabel())
            ? 'Select an %s'
            : 'Select a %s';
        $recordTitle = $this->formatValue($defaultValue);
        return (new RecordSelector(
            $context,
            $context->db->getTable($this->targetTable)
        ))->render(sprintf($prompt, $this->getLabel()), $recordTitle);
    }

    public function getReferredTableName(): string
    {
        return $this->targetTable;
    }
}
