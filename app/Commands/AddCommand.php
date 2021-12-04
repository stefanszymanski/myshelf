<?php

namespace App\Commands;

use App\Console\Dialog\CreateDialog;
use App\Context;
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

        $context = new Context($this->input, $this->output, $this->db);
        $createDialog = new CreateDialog($context, $table);
        $createDialog->render();
    }
}
