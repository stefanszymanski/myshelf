<?php

declare(strict_types=1);

namespace App\Console;

use App\Context;
use App\Persistence\Data\Field;
use App\Persistence\Data\StructField;

class EditStructDialog extends Dialog
{
    public function __construct(protected Context $context, protected StructField $field)
    {
        $this->output = $context->output;
        $this->db = $context->db;
    }

    /**
     * Render a struct edit dialog.
     *
     * @param array<string,mixed> $struct
     * @return array<string,mixed>|null The edited struct or `null` if the struct wasn't changed
     */
    public function render(array $struct): ?array
    {
        $newStruct = $struct;

        // Add a layer that prints the record table on each update.
        $layer = $this->context->addLayer(
            sprintf('Edit field "%s"', $this->field->getLabel()),
            function () use (&$struct, &$newStruct) {
                $this->displayStruct($struct, $newStruct);
            },
        );

        do {
            $exit = false;

            // Update the layer and ask for an action.
            $layer->update();
            $action = $this->output->ask("Enter action [#,d#,r#,r!,w,q,q!,wq,?]");

            switch ($action) {
                case '?':
                    // Display help.
                    $this->displayHelp();
                    break;
                case 'w':
                case 'wq':
                    // Keep changes to the struct and exit.
                    $exit = true;
                    break;
                case 'q':
                    // If the struct was changed: ask if it should be saved before exiting.
                    // If the struct wasn't changed: just exit.
                    if ($struct !== $newStruct) {
                        switch (strtolower($this->output->ask('Save changes? (Y)es, (N)o, [C]ancel', 'C'))) {
                            case 'y':
                                $exit = true;
                                break;
                            case 'n':
                                $this->warning('The changes were discarded');
                                $newStruct = [];
                                break;
                            default:
                                // do nothing
                        }
                    } else {
                        $exit = true;
                    }
                    break;
                case 'd!':
                    // TODO implement: iterate the fields and try to delete their values
                    break;
                case 'r!':
                    // Restore the struct to its original state.
                    $newStruct = $struct;
                    break;
                case 'q!':
                    // Dismiss all changes and exit.
                    $this->warning('The changes were discarded');
                    $newStruct = [];
                    $exit = true;
                    break;
                default:
                    // Edit, clear or restore a field.
                    list($action, $fieldNumber) = $this->parseFieldAction($action);
                    if (!$action) {
                        $this->error('Invalid action or field');
                        break;
                    }
                    switch ($action) {
                        case 'e':
                            $newStruct = $this->editField($newStruct, $fieldNumber);
                            break;
                        case 'd':
                            $newStruct = $this->clearField($newStruct, $fieldNumber);
                            break;
                        case 'r':
                            $newStruct = $this->restoreField($newStruct, $struct, $fieldNumber);
                            break;
                        default:
                            $this->error('Invalid action');
                            break;
                    }
            }
        } while (!$exit);

        // Remove undefined fields from the data.
        $newStruct = array_intersect_key($newStruct, $this->field->getSubFields());

        $layer->finish();
        return $newStruct;
    }

    /**
     * Display information about available actions and their keys.
     *
     * @return void
     */
    protected function displayHelp(): void
    {
        $lines = [
            ' # - edit field #',
            'd# - delete content of field #',
            'r# - restore field # to its original value',
            'r! - restore the struct to its original state',
            ' w - save changes and quit, also [wq]',
            ' q - quit (asks for confirmation if there are unsaved changes)',
            'q! - quit without saving',
            ' ? - print help',
        ];
        $this->context->enqueue(fn () => $this->output->text($lines));
    }

    /**
     * Display the struct and its changes as table.
     *
     * @param array<string,mixed> $struct
     * @param array<string,mixed> $newStruct
     * @return void
     */
    protected function displayStruct(array $struct, array $newStruct): void
    {
        (new DataTable($this->output))
            ->setFields($this->field->getSubFields())
            ->setData($struct)
            ->setNewData($newStruct)
            ->setDisplayFieldNumberColumn(true)
            ->render();
    }

    /**
     * Parse a field specific action into action string and element number.
     *
     * Examples:
     *   '3' => ['e', 3]
     *   'd12' => ['d', 12]
     *
     * Returns [null, null] if the field number is invalid, i.e. < 0 or > the number of fields.
     *
     * @param string $action
     * @return array{string|null,int|null} Next action and number of the selected field
     */
    protected function parseFieldAction(?string $action): array
    {
        if ($action === null) {
            return [null, null];
        }
        if (ctype_digit($action)) {
            $fieldNumber = $action;
            $action = 'e';
        } else {
            $fieldNumber = substr($action, 1);
            $action = substr($action, 0, 1);
        }
        $fields = $this->field->getSubFields();
        return !ctype_digit($fieldNumber) || (int) $fieldNumber < 1 || (int) $fieldNumber > sizeof($fields)
            ? [null, null]
            : [$action, (int) $fieldNumber];
    }

    /**
     * Edit a field.
     *
     * @param array<string,mixed> $struct
     * @param int $fieldNumber
     * @return array<string,mixed> Updated struct
     */
    protected function editField(array $struct, int $fieldNumber): array
    {
        $field = $this->getFieldByNumber($fieldNumber);
        $fieldName = $this->getFieldNameByNumber($fieldNumber);
        $struct[$fieldName] = $field->askForValue($this->context, $struct[$fieldName] ?? null);
        return $struct;
    }

    /**
     * Clear a field, i.e. delete its content.
     *
     * @param array<string,mixed> $struct
     * @param int $fieldNumber
     * @return array<string,mixed> Updated struct
     */
    protected function clearField(array $struct, int $fieldNumber): array
    {
        $field = $this->getFieldByNumber($fieldNumber);
        $fieldName = $this->getFieldNameByNumber($fieldNumber);
        if (!$field->validateValue($field->getEmptyValue())) {
            $this->error(sprintf('Field "%s" must not be empty', $field->getLabel()));
        } else {
            $struct[$fieldName] = $field->getEmptyValue();
        }
        return $struct;
    }

    /**
     * Restore a field to its original value.
     *
     * @param array<string,mixed> $struct
     * @param array<string,mixed> $originalStruct
     * @param int $fieldNumber
     * @return array<string,mixed> Updated struct
     */
    protected function restoreField(array $struct, array $originalStruct, int $fieldNumber): array
    {
        $fieldName = $this->getFieldNameByNumber($fieldNumber);
        $struct[$fieldName] = $originalStruct[$fieldName];
        return $struct;
    }

    protected function getFieldByNumber(int $fieldNumber): Field
    {
        return array_values($this->field->getSubFields())[$fieldNumber - 1];
    }

    protected function getFieldNameByNumber(int $fieldNumber): string
    {
        return array_keys($this->field->getSubFields())[$fieldNumber - 1];
    }
}
