<?php

namespace App\Commands;

use App\Persistence\Database;
use LaravelZero\Framework\Commands\Command;

class DeleteCommand extends Command
{
    protected $signature = 'rm {table} {key?*}
                            {--filter=* : Filter expression: <field><operator><value>}
                            {--delete-records : Delete referring records without confirmation}
                            {--derefer-records : Remove references from referring records}
                            {--no-summary : Do not display a summary of referring records}
    ';

    protected $description = 'Delete one or more records';

    public function __construct(protected Database $db)
    {
        parent::__construct();
    }

    public function handle()
    {
        $tableName = $this->argument('table');
        $keys = $this->argument('key');
        $filters = $this->option('filter');

        if (!empty($keys) && !empty($filters)) {
            $this->error('Argument --filter can not be used in conjunction with keys');
            return;
        }

        $table = $this->db->getTable($tableName);

        // Check if `$keys` contains integers. Those are IDs and they Key is determined.
        // TODO validate for invalid keys/ids
        $keys = array_filter(
            array_map(function ($key) use ($table) {
                if (ctype_digit($key)) {
                    $record = $table->store->findById($key);
                    $key = $record ? $record['key'] : null;
                }
                return $key;
            }, $keys)
        );

        // Find referring records.
        foreach ($keys as $key) {
            $referringRecords = $table->findReferringRecords($key);
            var_dump($referringRecords);
        }

        // TODO if there are referring records, display them and ask the user if:
        //      - the whole records should be deleted
        //      - the references in these records should be removed
        //      - the deletion should be aborted
        //      Respect the arguments --delete-records and --derefer-records
    }
}
