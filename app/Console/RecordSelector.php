<?php

declare(strict_types=1);

namespace App\Console;

use App\Console\CreateRecordDialog;
use Symfony\Component\Console\Question\Question;

class RecordSelector extends Dialog
{
    /**
     * Ask for a record with autocompletion.
     *
     * If the user inputs a value that is not in the autocomplete options,
     * it is asked if a new record may be created. If the user confirms,
     * a create dialog is started and the key of this newly created record gets returned.
     *
     * @param string|null $defaultAnswer
     * @return string|null Key of the selected record
     */
    public function render(?string $defaultAnswer = null): ?string
    {
        // TODO better question message, because when called from the CreateRecordDialog the user does not know which field he is asked for
        $options = $this->table->getAutocompleteOptions();
        list($exists, $value) = $this->askWithAutocompletion('Select a record', $options, $defaultAnswer);
        if ($exists) {
            // If the record exists, use it.
            $result = $value;
        } elseif (!$value) {
            // If the user input is empty, return null.
            $result = null;
        } else {
            // If the selected record doesn't exist, ask if it should be created.
            if (!$this->output->confirm("The selected record doesn't exist. Do you want to create it?", true)) {
                // If the user doesn't want to add a record, discard the user input and return null.
                $result = null;
            } else {
                // Create a new record.
                $defaults = $this->table->getDefaultsFromAutocompleteInput($value);
                $dialog = new CreateRecordDialog($this->context, $this->table);
                $record = $dialog->render($defaults);
                $result = $record['key'] ?? null;
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
    protected function askWithAutocompletion(string $question, array $options, ?string $defaultAnswer): array
    {
        $question = new Question($question, $defaultAnswer);
        $question->setAutocompleterValues(array_keys($options));
        $question->setNormalizer(function ($value) use ($options) {
            return match (true) {
                // Answer is in the autocomplete options
                array_key_exists($value, $options) => [true, $options[$value]],
                // Answer is in the record keys of the autocomplete options
                in_array($value, $options) => [true, $value],
                // Answer does not match an autocomplete option
                default => [false, $value],
            };
        });
        return $this->output->askQuestion($question);
    }
}
