<?php

namespace App\Commands;

use App\Persistence\Database;
use App\Persistence\Table;
use LaravelZero\Framework\Commands\Command;

class DeleteCommand extends Command
{
    protected $signature = 'rm {table} {key?*}
                            {--f|filter=* : Filter expression: <field><operator><value>}
                            {--d|delete-records : Delete referring records without confirmation}
                            {--r|derefer-records : Remove references from referring records}
                            {--i|ignore-invalid-keys : Do not abort due to invalid keys}
                            {--s|no-summary : Do not display a summary of referring records}
    ';

    protected $description = 'Delete one or more records';

    public function __construct(protected Database $db)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        if (!$this->validateArguments()) {
            return;
        }

        list($keys, $invalidKeys) = $this->getKeys();
        if (!empty($invalidKeys)) {
            $this->output->error(sprintf('Invalid keys: %s', implode(', ', $invalidKeys)));
            return;
        }

        $referringRecords = $this->getReferringRecords($keys);

        // If there aren't any referring records, ask for confirmation and delete.
        if (empty($referringRecords)) {
            $this->line('There are no referring records.');
            $this->newLine();
            $this->line('Following records will be deleted:');
            $this->output->listing($keys);
            $confirmed = $this->confirm('Do you really want to delete them?', false);
            if ($confirmed) {
                $table = $this->getTable();
                foreach (array_keys($keys) as $id) {
                    $table->store->deleteById($id);
                }
                $this->output->success('Records deleted');
            }
            return;
        }

        foreach ($keys as $id => $key) {
            $_referringRecords = $referringRecords[$key] ?? null;
            if (!$_referringRecords) {
                /* $this->output->info("No referring records for '$key'"); */
                continue;
            }
            foreach ($_referringRecords as $fieldName => $__referringRecords) {
                $this->output->warning("There are referring records for '$key [$id]' on field '$fieldName'");
                $this->table(['ID', 'Key'], $__referringRecords, 'box');
            }
            $this->output->error('No records where deleted due to referring records');
        }

        // TODO if there are referring records, display them and ask the user if:
        //      - the whole records should be deleted
        //      - the references in these records should be removed
        //      - the deletion should be aborted
        //      Respect the arguments --delete-records and --derefer-records
    }

    protected function getTable(): Table
    {
        return $this->db->getTable($this->argument('table'));
    }

    protected function validateArguments(): bool
    {
        $keys = $this->argument('key');
        $filters = $this->option('filter');
        if (!empty($keys) && !empty($filters)) {
            $this->output->error('Argument --filter can not be used in conjunction with keys');
            return false;
        }
        if (empty($keys) && empty($filters)) {
            $this->output->error('At least on key or --filter must be provided');
            return false;
        }
        return true;
    }

    protected function getKeys(): array
    {
        $table = $this->getTable();
        $keys = [];
        $invalidKeys = [];
        foreach ($this->argument('key') as $key) {
            $record = ctype_digit($key)
                ? $table->store->findById($key)
                : $table->store->findOneBy(['key', '=', $key]);
            if (!$record) {
                $invalidKeys[] = $key;
            } else {
                $keys[$record['id']] = $record['key'];
            }
        }
        return [$keys, $invalidKeys];
    }

    protected function getReferringRecords(array $keys): array
    {
        $table = $this->getTable();
        $records = [];
        foreach ($keys as $key) {
            $_records = $table->findReferringRecords($key);
            if (!empty($_records)) {
                $records[$key] = $_records;
            }
        }
        return $records;
    }
}
