<?php

namespace App\Console\Dialog;

use App\Configuration;
use App\Database;
use App\Validator\NewKeyValidator;
use App\Validator\NotEmptyValidator;
use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Console\OutputStyle;
use SleekDB\Store;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;

abstract class AbstractRecordDialog
{
    use InteractsWithIO;

    public function __construct(
        protected Database $db,
        protected Configuration $configuration,
        InputInterface $input,
        OutputStyle $output,
        int $verbosity,
    ) {
        $this->input = $input;
        $this->output = $output;
        $this->verbosity = $verbosity;
    }

    /**
     * Create and persist a record.
     *
     * @param array $defaults Record default values
     * @return string Key of the new record
     */
    abstract public function createRecord(array $defaults = []): string;

    /**
     * Build autocomplete options.
     *
     * Array keys must be the option labels, values are the record keys.
     *
     * @return array
     */
    abstract protected function buildAutocompleteOptions(): array;

    /**
     * Parse the user input from the record selection into record default values.
     *
     * @param string $value The user input
     * @return array Record defaults
     */
    abstract protected function getDefaultsFromInput(string $value): array;

    /**
     * Ask the user for a record with autocompletion.
     *
     * @param string $propertyName The name of the property that is used in prompts
     * @return string Key of the selected record
     */
    public function askForOne(string $propertyName): ?string
    {
        $prompt = sprintf('%s?', ucfirst($propertyName));
        return $this->_askForOne($prompt, $propertyName);
    }

    /**
     * Ask the user for a records with autocompletion.
     *
     * The user is asked for another record until he enters without any input.
     *
     * @param string $propertyName The name of the property that is used in prompts
     * @return array List of record keys
     */
    public function askForMultiple(string $propertyName): array
    {
        $results = [];

        while (true) {
            $prompt = empty($results)
                ? sprintf('%s?', ucfirst($propertyName))
                : sprintf('Another %s?', $propertyName);
            $result = $this->_askForOne($prompt, $propertyName);
            if ($result) {
                $results[] = $result;
            } else {
                break;
            }
        }

        return $results;
    }

    protected function _askForOne(string $prompt, string $propertyName): ?string
    {
        list($exists, $value) = $this->selectRecord($prompt);

        if ($exists) {
            // If the record exists, use it.
            $result = $value;
        } elseif (!$value) {
            // If the record doesn't exists and the input is empty, exit the loop.
            $result = null;
        } else {
            // If the selected record doesn't exist, ask if it should be created.
            if (!$this->confirm("The selected $propertyName doesn't exist. Do you want to create it?", true)) {
                // If the user doesn't want to add a record, exit the loop.
                return null;
            } else {
                // Create a new record.
                $defaults = $this->getDefaultsFromInput($value);
                $result = $this->createRecord($defaults);
            }
        }
        return $result;
    }

    /**
     * Ask for a person and autocomplete with existing ones.
     *
     * The return value is an array with two elements:
     * - First array element is true if the input was in the autocomplete options.
     * - Second array element is a string.
     *   If the first element is true, it's the person key. Otherwise it's the user input (may be null).
     */
    protected function selectRecord(string $prompt): array
    {
        // Prepare the question.
        $question = new Question($prompt);
        $options = $this->buildAutocompleteOptions();
        $question->setAutocompleterValues(array_keys($options));

        // Normalize the user selection.
        $question->setNormalizer(function ($value) use ($options) {
            if (array_key_exists($value, $options)) {
                return [true, $options[$value]];
            } else {
                return [false, $value];
            }
        });

        // Prompt the user and return the result.
        return $this->output->askQuestion($question);
    }

    /**
     * Ask a question until the answer is not empty.
     */
    protected function askMandatory(string $question, ?string $default = null)
    {
        return $this->askWithValidation($question, [new NotEmptyValidator], $default);
    }

    /**
     * Ask for a key.
     *
     * The key must not be empty and must be unique inside a given store.
     */
    protected function askForKey(string $question, Store $store, ?string $default = null)
    {
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
        return $this->output->ask($question, $default, function($answer) use ($validators) {
            foreach ($validators as $validator) {
                $answer = $validator($answer);
            }
            return $answer;
        });
    }
}
