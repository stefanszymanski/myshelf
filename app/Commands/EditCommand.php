<?php

namespace App\Commands;

use App\Console\EditDialog;
use App\Persistence\Database;
use LaravelZero\Framework\Commands\Command;

class EditCommand extends Command
{
    protected $signature = 'edit {table : Table name} {key : Record key or ID}';

    protected $description = 'Edit a record';


    public function __construct(protected Database $db)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $tableName = $this->argument('table');
        $table = $this->db->getTable($tableName);
        $record = $table->findByKeyOrId($this->argument('key'));

        if (!$record) {
            $this->output->error('Invalid record key or ID');
            return;
        }

        $editDialog = new EditDialog($this->input, $this->output, $this->db, $table);
        $editDialog->render($record);
    }
}
