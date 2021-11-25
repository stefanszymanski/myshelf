<?php

namespace App\Commands\Person;

use App\Commands\Command;
use App\Configuration;
use App\Database;
use App\Utility\RecordUtility;

class AddCommand extends Command
{
    protected $signature = 'person:add
                            {--key= : Key}
                            {--firstname= : First name}
                            {--lastname= : Last name}
                            {--nationality= : Nationality}
    ';

    protected $description = 'Add a person';

    public function handle(Database $db, Configuration $configuration)
    {
        // TODO determine Store and Type from classname
        // TODO add method to get a record from cli arguments
        // TODO respect argument for non-interactive mode
        $record = $this->recordDialog($this->options());
        $db->persons()->insert($record);
        $this->output->success('The record was created.');
    }

    protected function recordDialog(array $defaults = []): array
    {
        $type = $configuration->getType('person');
        $labels = $type->getFieldLabels(['key', 'firstname', 'lastname', 'nationality']);

        $this->output->title('New person record');

        $firstname = $this->ask($labels['firstname'], $defaults['firstname'] ?? null);
        $lastname = $this->askMandatory($labels['lastname'], $defaults['lastname'] ?? null);
        $nationality = $this->ask($labels['nationality'], $defaults['nationality'] ?? null);
        $defaultKey = RecordUtility::createKey($firstname, $lastname);
        $key = $this->askForKey($labels['key'], $db->persons(), $defaultKey);

        $record = [
            'key' => $key,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'nationality' => $nationality,
        ];

        $this->output->writeln(' <info>You entered the following data:</info>');
        $this->displayRecord($labels, $record);

        $repeat = $this->confirm('Do you want to change something?', false);
        if ($repeat) {
            $this->recordDialog($record);
        }
        return $record;
    }
}
