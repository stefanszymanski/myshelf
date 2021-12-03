<?php

declare(strict_types=1);

namespace App\Console;

use App\Persistence\Database;
use App\Persistence\ReferenceField;
use App\Persistence\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EditRecordDialog
{
    public function __construct(protected InputInterface $input, protected SymfonyStyle $output, protected Database $db, protected Table $table)
    {
    }

    /**
     * Render a record edit dialog.
     *
     * Supports persisted (i.e. records with ID) and new records.
     *
     * @param array<string,mixed> $record
     * @return void
     */
    public function render(array $record): void
    {
        $recordView = new RecordView($this->input, $this->output, $this->table);
        $recordView->renderEditTable($record);

        $newRecord = $record;

        do {
            // Check if the record is new or a persisted one, because there are small differences in behaviour:
            // - new records can't get deleted
            // - new records do not have an original state and therefore can't change
            $isExistingRecord = isset($record['id']);
            $supportedActions = $isExistingRecord
                ? '[#,d#,r#,d!,r!,s,w,q,q!,wq,?]'
                : '[#,d#,r#,r!,s,w,q,q!,wq,?]';
            $action = $this->output->ask("Enter action $supportedActions");

            $exit = false;
            switch ($action) {
                case '?':
                    // Display help.
                    $this->displayHelp($isExistingRecord);
                    break;
                case 's':
                    // Display the record.
                    $recordView->renderEditTable(
                        $isExistingRecord ? $record : $newRecord,
                        $isExistingRecord ? $newRecord : null
                    );
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
                        $this->output->success("Record was saved");
                        $exit = true;
                    }
                    break;
                case 'd!':
                    if (!$isExistingRecord) {
                        $this->output->error('Invalid command');
                    } elseif ($this->deleteRecord($record)) {
                        $exit = true;
                    }
                    break;
                case 'r!':
                    // Restore the record to its original state.
                    $newRecord = $record;
                    break;
                case 'q!':
                    // Dismiss all changes, just exit.
                    $exit = true;
                    break;
                default:
                    // Edit, clear or restore a field.
                    list($action, $fieldNumber) = $this->parseFieldAction($action);
                    if (!$action) {
                        $this->output->error('Invalid action or field');
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
                            $this->output->error('Invalid action');
                            break;
                    }
            }
        } while (!$exit);
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
            ' s - show record',
            ' w - save changes',
            ' q - quit (asks for confirmation if there are unsaved changes)',
            'q! - quit without saving',
            'wq - save changes and quit',
            ' ? - print help',
        ];
        if (!$isExistingRecord) {
            unset($lines[3]);
        }
        $this->output->text($lines);
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
            $record = $this->table->store->updateOrInsert($record);
            $this->output->success('Record was saved');
            $success = true;
        } catch (\Exception $e) {
            $this->output->error(sprintf('Record could not be saved: %s', $e->getMessage()));
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
        $deletionDialog = new DeleteRecordsDialog($this->input, $this->output, $this->db, $this->table);
        $deletedRecords = $deletionDialog->render($record['key']);
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
     */
    protected function parseFieldAction(string $action): array
    {
        $fields = $this->table->getFields2();
        if (ctype_digit($action)) {
            $fieldNumber = $action;
            $action = 'e';
        } else {
            $fieldNumber = substr($action, 1);
            $action = substr($action, 0, 1);
        }
        return !ctype_digit($fieldNumber) || (int) $fieldNumber < 1 || (int) $fieldNumber > sizeof($fields)
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
        if ($fieldNumber === 0) {
            // TODO edit key
        } else {
            $fields = $this->table->getFields2();
            $field = $fields[$fieldNumber - 1];
            $record[$field->name] = $field->ask($this->input, $this->output, $this->db, $record[$field->name] ?? null);
        }
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
        if ($fieldNumber === 0) {
            $this->output->error('The key must not be empty');
        } else {
            $fields = $this->table->getFields2();
            $field = $fields[$fieldNumber - 1];
            // TODO handle ReferenceFields, especially with multiple=true. Currently validators for
            //      ReferenceFields arent implemented, e.g. it's not possible to have mandatory reference fields
            if ($field instanceof ReferenceField) {
                $this->output->note('Clearing reference fields is not implemented, yet');
                return $record;
            }
            if (!$field->validate('')) {
                $this->output->error(sprintf('Field "%s" must not be empty', $field->label));
            } else {
                $record[$field->name] = null;
            }
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
        if ($fieldNumber === 0) {
            // TODO check if the key is still available, i.e. wasn't used for another record in the meanwhile
            $record['key'] = $originalRecord['key'];
        } else {
            $fields = $this->table->getFields2();
            $field = $fields[$fieldNumber - 1];
            $record[$field->name] = $originalRecord[$field->name];
        }
        return $record;
    }
}
