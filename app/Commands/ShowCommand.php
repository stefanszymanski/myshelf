<?php

namespace App\Commands;

use App\Console\DataTable;
use App\Persistence\Database;
use LaravelZero\Framework\Commands\Command;

class ShowCommand extends Command
{
    protected $signature = 'show {table : Table name} {key : Record key or ID}
                            {--r|raw : Display the raw record data, i.e. do not resolve references}
';

    protected $description = 'Show a record';

    public function __construct(protected Database $db)
    {
        parent::__construct();
    }

    // TODO per default show resolved references, e.g. author name
    // TODO mark reference with a fancy icon?
    // TODO implement --raw
    public function handle(): void
    {
        $table = $this->db->getTable($this->argument('table'));
        $record = $table->findByKeyOrId($this->argument('key'));

        if (!$record) {
            $this->output->error('Invalid record key');
            return;
        }

        (new DataTable($this->output))
            ->setFields($table->getFields())
            ->setData($record)
            ->setDisplayIdField(true)
            ->setDisplayKeyField(true)
            ->render();
    }
}
