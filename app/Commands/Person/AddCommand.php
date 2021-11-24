<?php

namespace App\Commands\Person;

use App\Configuration;
use App\Console\Dialog\PersonDialog;
use App\Database;
use LaravelZero\Framework\Commands\Command;

class AddCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'person:add';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Add a person';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(Database $db, Configuration $configuration)
    {
        $personDialog = new PersonDialog($db, $configuration, $this->input, $this->output, $this->verbosity);
        $personDialog->createRecord();
    }
}
