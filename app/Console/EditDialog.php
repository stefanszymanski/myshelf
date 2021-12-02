<?php

declare(strict_types=1);

namespace App\Console;

use App\Persistence\Database;
use App\Persistence\Field;
use App\Persistence\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EditDialog
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

        // Let the user edit the record until he exits.
        $newRecord = $record;
        do {
            // TODO add [R]eset all fields,
            // TODO streamline action keys with those from EditReferencesDialog
            // Check if the record is new or a persisted one, because new records can't get deleted.
            $isExistingRecord = isset($record['id']);
            $supportedActions = $isExistingRecord
                ? '[#,d#,D,r,w,q,wq,?]'
                : '[#,d#,r,w,q,wq,?]';
            $action = $this->output->ask("Enter action $supportedActions");
            if (!$isExistingRecord && $action === 'D') {
                $action = 'invalidAction';
            }
            $exit = false;

            // TODO move long cases to separate methods
            switch ($action) {
                case '?':
                    $this->displayHelp($isExistingRecord);
                    break;
                case 'r':
                    // Display the record.
                    $recordView->renderEditTable(
                        $isExistingRecord ? $record : $newRecord,
                        $isExistingRecord ? $newRecord : null
                    );
                    break;
                case 'w':
                    // Save the record.
                    if ($savedRecord = $this->saveRecord($newRecord)) {
                        $record = $savedRecord;
                        $newRecord = $record;
                    }
                    break;
                case 'q':
                    // If the record was changed: ask if it should be saved before exiting.
                    // If the record wasn't changed: just exit.
                    if ($record === $newRecord) {
                        $exit = true;
                    } else {
                        switch (strtolower($this->output->ask('Save changes? (Y)es, (N)o, [C]ancel', 'C'))) {
                            case 'y':
                                if ($this->saveRecord($newRecord)) {
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
                    if ($this->saveRecord($newRecord)) {
                        $this->output->success("Record was saved");
                        $exit = true;
                    }
                    break;
                case 'D':
                    // Delete the record after confirmation.
                    // TODO don't ever delete without checking for referring records!
                    break;
                    if ($this->output->confirm('Delete record?', false)) {
                        if ($this->table->store->deleteById($record['id'])) {
                            $this->output->success('Record was deleted');
                            $exit = true;
                        } else {
                            $this->output->error('Record could not be deleted');
                        }
                    }
                    break;
                default:
                    // If $action is a number: edit the field with this index.
                    // If $action is a number prefixed with d: clear this field.
                    if (str_starts_with($action, 'd')) {
                        $fieldNumber = substr($action, 1);
                        $clearField = true;
                    } else {
                        $fieldNumber = $action;
                        $clearField = false;
                    }
                    $fields = $this->table->getFields2();
                    // Check if the field number is valid.
                    if (!ctype_digit($fieldNumber) || $fieldNumber < 0 || $fieldNumber > sizeof($fields)) {
                        $this->output->error('Invalid field');
                        break;
                    }
                    $fieldNumber = (int) $fieldNumber;
                    if ($clearField) {
                        // Clear a field if it allows an empty value.
                        if ($fieldNumber === 0) {
                            $this->output->error('The key must not be empty');
                        } else {
                            $field = $fields[$fieldNumber - 1];
                            // TODO handle ReferenceFields, especially with multiple=true. Currently validators for
                            //      ReferenceFields arent implemented, e.g. it's not possible to have mandatory reference fields
                            if (!$field->validate('')) {
                                $this->output->error(sprintf('Field "%s" must not be empty', $field->label));
                                break;
                            }
                            $newRecord[$field->name] = null;
                        }
                    } else {
                        // Edit a field.
                        if ($fieldNumber === 0) {
                            // TODO edit key
                        } else {
                            $field = $fields[$fieldNumber - 1];
                            $newRecord[$field->name] = $this->askForField($field, $newRecord);
                        }
                    }
            }
        } while (!$exit);
    }

    /**
     * Ask for a value of the given field.
     *
     * @param Field $field
     * @param array<string,mixed> $record
     * @return mixed
     */
    protected function askForField(Field $field, array $record): mixed
    {
        return $field->ask($this->input, $this->output, $this->db, $record[$field->name] ?? null);
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
            ' # - edit field with number',
            'd# - clear field with number',
            ' D - delete the record',
            ' r - show record',
            ' w - save changes',
            ' q - quit',
            'wq - save changes and quit',
            ' ? - print help',
        ];
        if (!$isExistingRecord) {
            unset($lines[2]);
        }
        $this->output->text($lines);
    }

    /**
     * Save a record.
     *
     * @param array<string,mixed> $record Record to save
     * @return array<string,mixed>|null Either the saved record or null if the record could not be saved
     */
    protected function saveRecord(array $record): ?array
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
}
