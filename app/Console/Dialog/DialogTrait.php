<?php

declare(strict_types=1);

namespace App\Console\Dialog;

use App\Validator\NewKeyValidator;
use App\Validator\NotEmptyValidator;
use SleekDB\Store;
use Symfony\Component\Console\Question\Question;

trait DialogTrait
{
    /**
     * Prompt the user for input.
     */
    protected function ask(string $question, $default = null)
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
     */
    protected function askMandatory(string $question, $default = null)
    {
        return $this->askWithValidation($question, [new NotEmptyValidator], $default);
    }

    /**
     * Ask for a key.
     *
     * The key must not be empty and must be unique inside a given store.
     * If no store is given `$this->store` is used.
     */
    protected function askForKey(string $question, ?string $default = null, Store $store = null)
    {
        $store = $store ?? $this->repository->getStore();
        return $this->askWithValidation($question, [new NewKeyValidator($store)], $default);
    }

    /**
     * Ask with one or more validators.
     *
     * @param string $question
     * @param array<callable> $validators
     * @param string|null $default
     */
    protected function askWithValidation(string $question, array $validators, ?string $default = null)
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
     * @return string Key of the selected record
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
     * @return array List of record keys
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

    private function _askForRecord(string $typeName, string $question, string $propertyName): ?string
    {
        $type = $this->configuration->resolveType($typeName);
        $store = $this->configuration->getRepository($typeName)->getStore();
        // TODO is the Type class a good place for creating autocomplete options?
        $options = $type->getAutocompleteOptions($store);
        list($exists, $value) = $this->askWithAutocompletion($question, $options);
        if ($exists) {
            // If the record exists, use it.
            $result = $value;
        } elseif (!$value) {
            // If the user input is empty, return null.
            $result = null;
        } else {
            // If the selected record doesn't exist, ask if it should be created.
            if (!$this->confirm("The selected $propertyName doesn't exist. Do you want to create it?", true)) {
                // If the user doesn't want to add a record, discard the user input and return null.
                $result = null;
            } else {
                // Create a new record.
                $this->note(sprintf('Suspend %s creation, start creating a new %s', $this->typeName, $typeName));
                $defaults = $type->getDefaultsFromAutocompleteInput($value);
                $dialog = $this->getCreateDialog($typeName);
                $record = $dialog->run($defaults);
                $store->insert($record);
                $result = $record['key'];
                $this->note(sprintf('Finished %s creation, resume creating the %s', $typeName, $this->typeName));
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
