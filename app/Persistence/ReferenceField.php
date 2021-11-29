<?php

declare(strict_types=1);

namespace App\Persistence;

use App\Console\Dialog\CreateDialog;
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
            ? $this->askForRecords()
            : $this->askForRecord();
    }

    /**
     * Ask the user for a record with autocompletion.
     *
     * @return string|null Key of the selected record
     */
    protected function askForRecord(): ?string
    {
        return $this->_askForRecord($this->label);
    }

    /**
     * Ask the user for a records with autocompletion.
     *
     * The user is asked for another record until he enters without any input.
     *
     * @param string $type Type of the record to ask for
     * @param string $propertyName The name of the property that is used in prompts
     * @return array<string> List of record keys
     */
    protected function askForRecords(): array
    {
        $results = [];
        while (true) {
            $question = empty($results)
                ? ucfirst($this->label)
                : sprintf('Another %s', lcfirst($this->label));
            $result = $this->_askForRecord($question);
            if ($result) {
                $results[] = $result;
            } else {
                break;
            }
        }
        return $results;
    }

    /**
     * Ask for a record with autocompletion.
     *
     * If the user inputs a value that is not in the autocomplete options,
     * it is asked if a new record may be created. If the user confirms,
     * a create dialog is started and the key of this newly created record gets returned.
     *
     * @param string $tableName
     * @param string $question
     * @param string $fieldName
     * @return string|null Key of the selected record
     */
    private function _askForRecord(string $question): ?string
    {
        $table = $this->db->getTable($this->foreignTable);
        $options = $table->getAutocompleteOptions();
        $fieldName = lcfirst($this->label);
        list($exists, $value) = $this->askWithAutocompletion($question, $options);
        if ($exists) {
            // If the record exists, use it.
            $result = $value;
        } elseif (!$value) {
            // If the user input is empty, return null.
            $result = null;
        } else {
            // If the selected record doesn't exist, ask if it should be created.
            if (!$this->output->confirm("The selected $fieldName doesn't exist. Do you want to create it?", true)) {
                // If the user doesn't want to add a record, discard the user input and return null.
                $result = null;
            } else {
                // Create a new record.
                $this->output->note(sprintf('Suspend %s creation, start creating a new %s', $this->table, $this->foreignTable));
                $defaults = $table->getDefaultsFromAutocompleteInput($value);
                $dialog = new CreateDialog($this->input, $this->output, $this->db, $table);
                $record = $dialog->run($defaults);
                $table->store->insert($record);
                $result = $record['key'];
                $this->output->note(sprintf('Finished %s creation, resume creating the %s', $this->foreignTable, $this->table));
            }
        }
        return $result;
    }

    /**
     * Ask with autocompletion.
     *
     * @param string $question
     * @param array<string,string> $options Keys are the displayed options, values are the value to return on selection
     * @return array{bool,string|null} First element is whether the input was in `$options`, second element is the user input
     */
    protected function askWithAutocompletion(string $question, array $options): array
    {
        $question = new Question($question);
        $question->setAutocompleterValues(array_keys($options));
        $question->setNormalizer(function ($value) use ($options) {
            return array_key_exists($value, $options)
                ? [true, $options[$value]]
                : [false, $value];
        });
        return $this->output->askQuestion($question);
    }
}
