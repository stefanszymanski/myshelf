<?php

declare(strict_types=1);

namespace App\Console\Dialog;

use App\Persistence\Database;
use App\Persistence\Table;
use App\Validator\NewKeyValidator;
use App\Validator\NotEmptyValidator;
use SleekDB\Store;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

// TODO remove as soon as all functionality is moved to other classes
trait DialogTrait
{
    protected InputInterface $input;

    protected SymfonyStyle $output;

    protected Database $db;

    protected Table $table;

    /**
     * Ask a question.
     *
     * @param string $question
     * @param mixed $default
     * @return mixed
     */
    protected function ask(string $question, mixed $default = null): mixed
    {
        return $this->output->ask($question, $default);
    }

    /**
     * Confirm a question with the user.
     *
     * @param  string  $question
     * @param  bool  $default
     * @return bool
     */
    protected function confirm(string $question, bool $default = false): bool
    {
        return $this->output->confirm($question, $default);
    }

    /**
     * Ask a question until the answer is not empty.
     *
     * @param string $question
     * @param mixed $default
     * @return mixed
     */
    protected function askMandatory(string $question, mixed $default = null): mixed
    {
        return $this->askWithValidation($question, [new NotEmptyValidator], $default);
    }

    /**
     * Ask for a key.
     *
     * The key must not be empty and must be unique inside a given store.
     * If no store is given `$this->store` is used.
     *
     * @param string $question
     * @param string|null $default
     * @param Store $store
     * @return null|string
     */
    protected function askForKey(string $question, ?string $default = null, Store $store = null): ?string
    {
        $store = $store ?? $this->table->store;
        return $this->askWithValidation($question, [new NewKeyValidator($store)], $default);
    }

    /**
     * Ask with one or more validators.
     *
     * @param string $question
     * @param array<callable> $validators
     * @param string|null $default
     * @return mixed
     */
    protected function askWithValidation(string $question, array $validators, ?string $default = null): mixed
    {
        return $this->output->ask($question, $default, function ($answer) use ($validators) {
            foreach ($validators as $validator) {
                $answer = $validator($answer);
            }
            return $answer;
        });
    }

    /**
     * Ask the user for a record with autocompletion.
     *
     * @param string $type Type of the record to ask for
     * @param string $propertyName The name of the property that is used in prompts
     * @return string|null Key of the selected record
     */
    protected function askForRecord(string $type, string $propertyName): ?string
    {
        $question = ucfirst($propertyName);
        return $this->_askForRecord($type, $question, $propertyName);
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
    protected function askForRecords(string $type, string $propertyName): array
    {
        $results = [];
        while (true) {
            $prompt = empty($results)
                ? ucfirst($propertyName)
                : sprintf('Another %s', $propertyName);
            $result = $this->_askForRecord($type, $prompt, $propertyName);
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
    private function _askForRecord(string $tableName, string $question, string $fieldName): ?string
    {
        $table = $this->db->getTable($tableName);
        $options = $table->getAutocompleteOptions();
        list($exists, $value) = $this->askWithAutocompletion($question, $options);
        if ($exists) {
            // If the record exists, use it.
            $result = $value;
        } elseif (!$value) {
            // If the user input is empty, return null.
            $result = null;
        } else {
            // If the selected record doesn't exist, ask if it should be created.
            if (!$this->confirm("The selected $fieldName doesn't exist. Do you want to create it?", true)) {
                // If the user doesn't want to add a record, discard the user input and return null.
                $result = null;
            } else {
                // Create a new record.
                $this->note(sprintf('Suspend %s creation, start creating a new %s', $this->table->name, $table->name));
                $defaults = $table->getDefaultsFromAutocompleteInput($value);
                $dialog = $this->getCreateDialog($tableName);
                $record = $dialog->run($defaults);
                $table->store->insert($record);
                $result = $record['key'];
                $this->note(sprintf('Finished %s creation, resume creating the %s', $table->name, $this->table->name));
            }
        }
        return $result;
    }

    /**
     * Display a note.
     *
     * @param string $message
     * @return void
     */
    protected function note(string $message): void
    {
        $this->output->block($message, 'NOTE', 'fg=black;bg=blue', ' ', true);
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

    /**
     * @see CreateDialogTrait::getCreateDialog()
     */
    abstract protected function getCreateDialog(string $type): DialogInterface;
}
