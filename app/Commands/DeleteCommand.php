<?php

namespace App\Commands;

use App\Console\DeleteRecordsDialog;
use App\Context;
use App\Persistence\Database;
use LaravelZero\Framework\Commands\Command;

class DeleteCommand extends Command
{
    protected $signature = 'rm {table : Table name} {key?* : Record key or ID}
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

        $tableName = $this->argument('table');
        $table = $this->db->getTable($tableName);

        $context = new Context($this->input, $this->output, $this->db);
        (new DeleteRecordsDialog($context, $table))->render(...$this->argument('key'));
        $context->flush();

        //      Respect the arguments --delete-records and --derefer-records
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
}
