<?php

namespace App\Commands\Book;

use App\Console\Dialog\BookDialog;
use App\Database;
use LaravelZero\Framework\Commands\Command;

class AddCommand extends Command
{
    protected $signature = 'book:add
                            {--title= : Title}
                            {--author=* : Author}
                            {--editor=* : Editor}
                            {--publisher= : Publisher}
                            {--published= : Publishing year}
                            {--acquired= : Acquiration date}
    ';

    protected $description = 'Add a book';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(Database $db)
    {
        $bookDialog = new BookDialog($db, $this->input, $this->output, $this->verbosity);
        $bookDialog->createRecord($this->options());
    }
}
