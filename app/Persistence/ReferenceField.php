<?php

declare(strict_types=1);

namespace App\Persistence;

use App\Console\EditReferencesDialog;
use App\Console\RecordSelector;
use App\Context;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class ReferenceField extends Field
{
    public function __construct(
        public readonly string $table,
        public readonly string $name,
        public readonly string $foreignTable,
        public readonly bool $multiple,
        public readonly string $label,
        public readonly ?string $description = null,
        protected array $validators = [],
        protected ?\Closure $formatter = null,
    ) {
    }

    /**
     * Get value for an empty field state.
     *
     * @return array<string>|null
     */
    public function getEmptyValue(): mixed
    {
        return $this->multiple
            ? []
            : null;
    }

    public function ask(Context $context, mixed $defaultAnswer = null): mixed
    {
        return $this->multiple
            ? $this->askForRecords($context, $defaultAnswer ?? [])
            : $this->askForRecord($context, $defaultAnswer ?? null);
    }

    /**
     * Ask the user for a record with autocompletion.
     *
     * @return string|null Key of the selected record
     */
    protected function askForRecord(Context $context, ?string $defaultAnswer): ?string
    {
        return (new RecordSelector(
            $context,
            $context->db->getTable($this->foreignTable))
        )->render(sprintf('Select a %s', $this->label), $defaultAnswer);
    }

    /**
     * Ask the user for a records with autocompletion.
     *
     * The user is asked for another record until he enters without any input.
     *
     * @param array<string> $defaultAnswer List of record keys
     * @return array<string> List of record keys
     */
    protected function askForRecords(Context $context, array $defaultAnswer = []): array
    {
        return (new EditReferencesDialog(
            $context,
            $context->db->getTable($this->foreignTable))
        )->render($this, $defaultAnswer);
    }
}
