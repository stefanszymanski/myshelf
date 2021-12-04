<?php

namespace App\Commands;

use App\Console\EditRecordDialog;
use App\Context;
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

        $context = new Context($this->input, $this->output, $this->db);
        $editDialog = new EditRecordDialog($context, $table);
        $editDialog->render($record);
        $context->flush();
    }
}
