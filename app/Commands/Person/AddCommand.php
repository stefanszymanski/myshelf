<?php

namespace App\Commands\Person;

use App\Database;
use App\Utility\RecordUtility;
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
    public function handle(Database $db)
    {
        $firstname = $this->ask('First name?');
        $lastname = $this->ask('Last name?');
        $nationality = $this->ask('Nationality?');
        $key = $this->ask('Key?', RecordUtility::createKey($firstname, $lastname));

        $db->getPersons()->insert([
            'key' => $key,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'nationality' => $nationality,
        ]);
    }
}
