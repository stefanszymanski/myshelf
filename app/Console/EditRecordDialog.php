<?php

declare(strict_types=1);

namespace App\Console;

use App\Persistence\Data\Field;

class EditRecordDialog extends Dialog
{
    /**
     * Render a record edit dialog.
     *
     * Supports persisted (i.e. records with ID) and new records.
     *
     * @param array<string,mixed> $record
     * @return array<string,mixed>|null The edited record or null if the record wasn't changed
     */
    public function render(array $record): ?array
    {
        // Initialize data property.
        if (!isset($record['data'])) {
            $record['data'] = [];
        }
        if (!isset($record['meta'])) {
            $record['meta'] = [];
        }

        $newRecord = $record;

        // Add a layer that prints the record table on each update.
        $layer = $this->context->addLayer(
            __('breadcrumb.editrecord', [
                'table' => __(sprintf('schema.%s.label', $this->table->name)),
                'record' => $this->table->getRecordTitle($record),
            ]),
            function () use (&$record, &$newRecord) {
                $this->displayRecord($record, $newRecord);
            },
        );

        do {
            $exit = false;
            // Check if the record is new or a persisted one, because there are small differences in behaviour:
            // - new records can't get deleted
            // - new records do not have an original state and therefore can't change
            $isExistingRecord = isset($record['id']);

            // Update the layer and ask for an action.
            $layer->update();
            $supportedActions = $isExistingRecord
                ? '[#,d#,r#,d!,r!,w,q,q!,wq,?]'
                : '[#,d#,r#,r!,w,q,q!,wq,?]';
            $action = $this->output->ask("Enter action $supportedActions");

            switch ($action) {
                case '?':
                    // Display help.
                    $this->displayHelp($isExistingRecord);
                    break;
                case 'w':
                    // Save the record.
                    if ($savedRecord = $this->persistRecord($newRecord)) {
                        $record = $savedRecord;
                        $newRecord = $record;
                    }
                    break;
                case 'q':
                    // If the record was changed: ask if it should be saved before exiting.
                    // If the record wasn't changed: just exit.
                    if ($isExistingRecord && $record === $newRecord) {
                        $exit = true;
                    } else {
                        switch (strtolower($this->output->ask('Save changes? (Y)es, (N)o, [C]ancel', 'C'))) {
                            case 'y':
                                if ($this->persistRecord($newRecord)) {
                                    $exit = true;
                                }
                                break;
                            case 'n':
                                if (!$isExistingRecord) {
                                    $this->warning('The new record was discarded');
                                    $newRecord = null;
                                }
                                $exit = true;
                                break;
                            default:
                                // do nothing
                        }
                    }
                    break;
                case 'wq':
                    // Save the record and exit if successful.
                    if ($this->persistRecord($newRecord)) {
                        $exit = true;
                    }
                    break;
                case 'd!':
                    if (!$isExistingRecord) {
                        $this->error('Invalid command');
                    } elseif ($this->deleteRecord($record)) {
                        $exit = true;
                        $newRecord = null;
                    }
                    break;
                case 'r!':
                    // Restore the record to its original state.
                    $newRecord = $record;
                    break;
                case 'q!':
                    // Dismiss all changes, just exit.
                    if (!$isExistingRecord) {
                        $this->warning('The new record was discarded');
                        $newRecord = null;
                    }
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
                            $newRecord = $this->editField($newRecord, $fieldNumber);
                            break;
                        case 'd':
                            $newRecord = $this->clearField($newRecord, $fieldNumber);
                            break;
                        case 'r':
                            $newRecord = $this->restoreField($newRecord, $record, $fieldNumber);
                            break;
                        default:
                            $this->error('Invalid action');
                            break;
                    }
            }
        } while (!$exit);

        $layer->finish();
        return $newRecord;
    }

    /**
     * Display information about available actions and their keys.
     *
     * @param bool $isExistingRecord
     * @return void
     */
    protected function displayHelp(bool $isExistingRecord): void
    {
        $lines = [
            ' # - edit field #',
            'd# - delete content of field #',
            'r# - restore field # to its original value',
            'd! - delete the record',
            'r! - restore the record to its original state',
            ' w - save changes',
            ' q - quit (asks for confirmation if there are unsaved changes)',
            'q! - quit without saving',
            'wq - save changes and quit',
            ' ? - print help',
        ];
        if (!$isExistingRecord) {
            unset($lines[3]);
        }
        $this->context->enqueue(fn () => $this->output->text($lines));
    }

    /**
     * @param array<string,mixed> $record
     * @param array<string,mixed> $newRecord
     * @return void
     */
    protected function displayRecord(array $record, array $newRecord): void
    {
        $isExistingRecord = isset($record['id']);
        $data = $record['data'];
        $newData = $newRecord['data'];
        if ($isExistingRecord) {
            $data['id'] = $record['id'];
            $newData['id'] = $newRecord['id'];
        }
        (new DataTable($this->output))
            ->setFields($this->table->getDataFields())
            ->setData($isExistingRecord ? $data : $newData)
            ->setNewData($isExistingRecord ? $newData : null)
            ->setDisplayIdField(true)
            ->setDisplayFieldNumberColumn(true)
            ->render();
    }

    /**
     * Persist a record.
     *
     * @param array<string,mixed> $record Record to save
     * @return array<string,mixed>|null Either the saved record or null if the record could not be saved
     */
    protected function persistRecord(array $record): ?array
    {
        try {
            // TODO move setting the update date somewhere else. wrap the store with a custom class that handles such stuff.
            $record['meta']['updated'] = time();
            $record = $this->table->store->updateOrInsert($record);
            $this->success('Record was saved');
            $success = true;
        } catch (\Exception $e) {
            $this->error(sprintf('Record could not be saved: %s', $e->getMessage()));
            $success = false;
        }
        return $success ? $record : null;
    }

    /**
     * Delete the record.
     *
     * @param array<string,mixed> $record
     * @return bool Whether the record was deleted
     */
    protected function deleteRecord(array $record): bool
    {
        $deletionDialog = new DeleteRecordsDialog($this->context, $this->table);
        $deletedRecords = $deletionDialog->render($record['id']);
        return !empty($deletedRecords);
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
     *
     * TODO move method somewhere else, because EditStructDialog and EditListDialog contain the same
     */
    protected function parseFieldAction(?string $action): array
    {
        if ($action === null) {
            return [null, null];
        }
        $fields = $this->table->getDataFields();
        if (ctype_digit($action)) {
            $fieldNumber = $action;
            $action = 'e';
        } else {
            $fieldNumber = substr($action, 1);
            $action = substr($action, 0, 1);
        }
        return !ctype_digit($fieldNumber) || (int) $fieldNumber < 0 || (int) $fieldNumber > sizeof($fields)
            ? [null, null]
            : [$action, (int) $fieldNumber];
    }

    /**
     * Edit a field.
     *
     * @param array<string,mixed> $record
     * @param int $fieldNumber
     * @return array<string,mixed> Updated record
     */
    protected function editField(array $record, int $fieldNumber): array
    {
        $field = $this->getFieldByNumber($fieldNumber);
        $fieldName = $this->getFieldNameByNumber($fieldNumber);
        // TODO localize prompt, as the prompt is part of the field asking for a value, it must go there
        $record['data'][$fieldName] = $field->askForValue($this->context, $record['data'][$fieldName] ?? null);
        return $record;
    }

    /**
     * Clear a field, i.e. delete its content.
     *
     * @param array<string,mixed> $record
     * @param int $fieldNumber
     * @return array<string,mixed> Updated record
     */
    protected function clearField(array $record, int $fieldNumber): array
    {
        $field = $this->getFieldByNumber($fieldNumber);
        $fieldName = $this->getFieldNameByNumber($fieldNumber);
        if (!$field->validateValue($field->getEmptyValue())) {
            // TODO localize
            $this->error(sprintf('Field "%s" must not be empty', $fieldName));
        } else {
            $record['data'][$fieldName] = $field->getEmptyValue();
        }
        return $record;
    }

    /**
     * Restore a field to its original value.
     *
     * @param array<string,mixed> $record
     * @param array<string,mixed> $originalRecord
     * @param int $fieldNumber
     * @return array<string,mixed> Updated record
     */
    protected function restoreField(array $record, array $originalRecord, int $fieldNumber): array
    {
        $fieldName = $this->getFieldNameByNumber($fieldNumber);
        $record['data'][$fieldName] = $originalRecord['data'][$fieldName];
        return $record;
    }

    protected function getFieldByNumber(int $fieldNumber): Field
    {
        return array_values($this->table->getDataFields())[$fieldNumber - 1];
    }

    protected function getFieldNameByNumber(int $fieldNumber): string
    {
        return array_keys($this->table->getDataFields())[$fieldNumber - 1];
    }
}
