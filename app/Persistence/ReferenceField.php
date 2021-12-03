<?php

declare(strict_types=1);

namespace App\Persistence;

use App\Console\Dialog\CreateDialog;
use App\Console\EditReferencesDialog;
use App\Console\RecordSelector;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class ReferenceField extends Field
{
    protected InputInterface $input;
    protected SymfonyStyle $output;
    protected Database $db;

    public function __construct(
        public readonly string $table,
        public readonly string $name,
        public readonly string $foreignTable,
        public readonly bool $multiple,
        public readonly string $label,
        public readonly ?string $description = null,
        protected ?\Closure $formatter = null,
    ) {
    }

    public function ask(InputInterface $input, SymfonyStyle $output, Database $db, mixed $defaultAnswer = null): mixed
    {
        $this->input = $input;
        $this->output = $output;
        $this->db = $db;
        return $this->multiple
            ? $this->askForRecords($defaultAnswer)
            : $this->askForRecord($defaultAnswer);
    }

    /**
     * Ask the user for a record with autocompletion.
     *
     * @return string|null Key of the selected record
     */
    protected function askForRecord(?string $defaultAnswer): ?string
    {
        return (new RecordSelector($this->input, $this->output, $this->db, $this->db->getTable($this->foreignTable)))->render($defaultAnswer);
    }

    /**
     * Ask the user for a records with autocompletion.
     *
     * The user is asked for another record until he enters without any input.
     *
     * @param array<string> $defaultAnswer List of record keys
     * @return array<string> List of record keys
     */
    protected function askForRecords(array $defaultAnswer = []): array
    {
        return (new EditReferencesDialog($this->input, $this->output, $this->db, $this->db->getTable($this->foreignTable)))->render($defaultAnswer);
    }
}
