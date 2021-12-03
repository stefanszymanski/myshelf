<?php

namespace App\Commands;

use App\Console\Dialog\CreateDialog;
use App\Console\EditRecordDialog;
use App\Persistence\Database;
use LaravelZero\Framework\Commands\Command;

class AddCommand extends Command
{
    protected $signature = 'add {table : Table name}';

    protected $description = 'Create a new record';

    public function __construct(protected Database $db)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $table = $this->db->getTable($this->argument('table'));

        $createDialog = new CreateDialog($this->input, $this->output, $this->db, $table);
        $record = $createDialog->render();

        // TODO should I move the following lines to the CreateDialog?
        $this->output->writeln(' <info>You entered the following data:</info>');
        $editDialog = new EditRecordDialog($this->input, $this->output, $this->db, $table);
        $editDialog->render($record);
    }
}
