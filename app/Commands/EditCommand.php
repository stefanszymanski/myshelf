<?php

namespace App\Commands;

use App\Persistence\Database;
use App\Persistence\Table;
use LaravelZero\Framework\Commands\Command;

class EditCommand extends Command
{
    protected $signature = 'edit {table} {key}';

    protected $description = 'Edit a record';

    public function __construct(protected Database $db)
    {
        parent::__construct();
    }

    public function handle()
    {
        $record = $this->getRecord();
        if (!$record) {
            $this->output->error('Invalid record key');
            return;
        }

        $table = $this->getTable();
        $fields = $table->getFields2();

        // TODO move displayRecord to separate class
        $this->displayRecord($record);

        // TODO move this dialog to a separate class
        $newRecord = $record;
        $exit = false;
        do {
            // TODO action for clearing a field
            $action = $this->ask('Edit field [#,r,a,q,?]');
            switch ($action) {
                case '?':
                    $this->displayHelp();
                    break;
                case 'r':
                    $this->displayRecord($record, $newRecord);
                    break;
                case 'a':
                    $exit = true;
                    break;
                case 'q':
                    // TODO save record
                    $exit = true;
                    break;
                default:
                    if (!isset($fields[$action])) {
                        $this->error('Invalid action');
                        break;
                    }
                    $field = $fields[$action];
                    // TODO implement Field::ask()
                    $newRecord[$field->name] = $field->ask($this->input, $this->output, $newRecord[$field->name]);
            }
        } while (!$exit);
    }

    protected function getTable(): Table
    {
        return $this->db->getTable($this->argument('table'));
    }

    // TODO move this method to class Table
    protected function getRecord(): ?array
    {
        $table = $this->getTable();
        $key = $this->argument('key');
        return ctype_digit($key)
            ? $table->store->findById($key)
            : $table->store->findOneBy(['key', '=', $key]);
    }

    protected function displayRecord(array $record, array $newRecord = null): void
    {
        $table = $this->getTable();
        $fields = $table->getFields2();

        // Display record
        // TODO display record as table, first column is a number or character that the user has to type to edit this field
        $rows = [];
        for ($i = 0; $i < sizeof($fields); $i++) {
            $field = $fields[$i];
            $value = $field->valueToString($record[$field->name]);
            $row = [
                $i,
                $field->label,
                $value,
            ];
            if ($newRecord) {
                $newValue = $field->valueToString($newRecord[$field->name]);
                if ($value !== $newValue) {
                    $newValue = sprintf('<fg=yellow>%s</>', $newValue);
                }
                $row[] = $newValue;
            }
            $rows[] = $row;
        }

        $headers = ['Key', 'Field', 'Value'];
        if ($newRecord) {
            $headers[] = 'New value';
        }

        // TODO render better table with headlines in the second row
        $this->table($headers, $rows, 'box');
    }

    protected function displayHelp(): void
    {
        $this->output->text([
            '  - edit field with number',
            'r - show record',
            'q - save changes and exit',
            'a - dismiss changes and exit',
            '? - print help',
        ]);
    }
}
