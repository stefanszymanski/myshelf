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
    protected function askForRecords(array $defaultAnswer): array
    {
        // TODO move to new class EditReferencesDialog
        // TODO action for moving a line up/down?
        // TODO move cases to separate methods
        $elements = array_map(fn ($answer) => [$answer, $answer], $defaultAnswer);
        do {
            $exit = false;
            if (empty($elements)) {
                $action = 'n';
            } else {
                $action = $this->output->ask("Enter action [#,d#,r#,a,R,D,l,w,a,?]");
            }
            switch ($action) {
                case '?':
                    // TODO display help
                    break;
                case 'l':
                    // Display element as table
                    // TODO render a prettier table
                    // TODO display headlines in blue, not only here but everywhere.
                    //      The default green should only be used for indicating new values
                    //      Or maybe another color than blue?
                    $rows = [];
                    for ($i = 0; $i < sizeof($elements); $i++) {
                        list($old, $new) = $elements[$i];
                        $format = match (true) {
                            $old === $new => '%1$s',
                            $old !== null && $new === null => '<fg=red>%1$s</>',
                            $old === null && $new !== null => '<fg=green>%2$s</>',
                            $old !== $new => '<fg=red>%1$s</> <fg=green>%2$s</>',
                            default => throw new \UnexpectedValueException('This should not happen'),
                        };
                        $rows[] = [$i + 1, sprintf($format, $old, $new)];
                    }
                    $this->output->table(['#', 'Values'], $rows);
                    break;
                case 'w':
                case 'q':
                case 'wq':
                    // Finish
                    $exit = true;
                    break;
                case 'D':
                    // Remove all elements.
                    $elements = array_map(
                        fn ($element) => [$element[0], null],
                        array_filter($elements, fn ($element) => $element[0] !== null)
                    );
                    break;
                case 'A':
                    // Dismiss all changes and return
                    $exit = true;
                case 'R':
                    // Reset all elements.
                    $elements = array_map(
                        fn ($element) => [$element[0], $element[0]],
                        array_filter($elements, fn ($element) => $element[0] !== null)
                    );
                    break;
                case 'a':
                case 'n':
                    // Add a new element.
                    $newValue = $this->_askForRecord('Select a record');
                    if (!$newValue) {
                        // Dismiss if no record was selected
                        break;
                    }
                    if (in_array($newValue, array_column($elements, 1))) {
                        // Display warning and dismiss if the record was already selected.
                        $this->output->warning("Record $newValue is already selected");
                        break;
                    }
                    $elements[] = [null, $newValue];
                    break;
                default:
                    // Edit, remove or restore an element
                    // Determine action and element number
                    if (ctype_digit($action)) {
                        $n = $action;
                        $action = 'e';
                    } else {
                        $n = substr($action, 1);
                        $action = substr($action, 0, 1);
                    }
                    // Validate the element number
                    if (!ctype_digit($n) || (int) $n < 1 || (int) $n > sizeof($elements)) {
                        $this->output->error('Invalid element');
                        break;
                    }
                    $n = (int) $n - 1;
                    switch ($action) {
                        case 'e':
                            // Edit element
                            $newValue = $this->_askForRecord('Select record', $elements[$n][1]);
                            if (!$newValue) {
                                $this->output->warning('Nothing was selected, selection was dismissed');
                                break;
                            }
                            $elements[$n][1] = $newValue;
                            break;
                        case 'd':
                            // Remove answer
                            if ($elements[$n][0] !== null) {
                                $elements[$n][1] = null;
                            } else {
                                unset($elements[$n]);
                            }
                            break;
                        case 'r':
                            // Restore answer
                            if ($elements[$n][0] !== null) {
                                $elements[$n][1] = $elements[$n][0];
                            }
                            break;
                        default:
                            $this->output->error('Invalid action');
                            break;
                    }
            }
        } while (!$exit);
        return array_filter(array_column($elements, 1));
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
    private function _askForRecord(string $question, ?string $defaultAnswer = null): ?string
    {
        $table = $this->db->getTable($this->foreignTable);
        $options = $table->getAutocompleteOptions();
        $fieldName = lcfirst($this->label);
        list($exists, $value) = $this->askWithAutocompletion($question, $options, $defaultAnswer);
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
                $record = $dialog->render($defaults);
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
