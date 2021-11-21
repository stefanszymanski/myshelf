<?php

namespace App\Console\Dialog;

use App\Utility\RecordUtility;

class PersonDialog extends AbstractRecordDialog
{
    /**
     * Build autocomplete options.
     *
     * Fetches all persons and build two autocomplete options for each:
     * {firstname} {lastname}
     * {lastname}, {firstname}
     */
    protected function buildAutocompleteOptions(): array
    {
        $records = $this->db->persons()->createQueryBuilder()
            ->select(['key', 'firstname', 'lastname'])
            ->getQuery()
            ->fetch();
        $options = [];
        foreach ($records as $record) {
            $options["{$record['firstname']} {$record['lastname']}"] = $record['key'];
            $options["{$record['lastname']}, {$record['firstname']}"] = $record['key'];
        }
        return $options;
    }

    /**
     * Parse the user input from the record selection into record default values.
     *
     * Splits the input into firstname and lastname.
     *
     * @param string $value The user input
     * @return array Record defaults
     */
    protected function getDefaultsFromInput(string $value): array
    {
        if (str_contains($value, ',')) {
            // If the user input contains a comma: use the first part as lastname and the rest as firstname.
            list($lastname, $firstname) = array_map('trim', explode(',', $value, 2));
        } else {
            // Otherwise use everything before the last space as firstname and the rest as lastname.
            $parts = explode(' ', $value);
            $lastname = trim(array_pop($parts));
            $firstname = trim(implode(' ', $parts));
        }
        return [
            'firstname' => $firstname,
            'lastname' => $lastname,
        ];
    }

    /**
     * Create and persist a record.
     *
     * @param array $defaults Record default values
     * @return string Key of the new record
     */
    public function createRecord(array $defaults = []): string
    {
        // Prompt for person data and persist.
        $firstname = $this->ask('First name?', $defaults['firstname'] ?? null);
        /* $lastname = $this->ask('Last name?', $defaults['lastname'] ?? null); */
        $lastname = $this->askMandatory('Last name?', $defaults['lastname'] ?? null);
        $nationality = $this->ask('Nationality?');
        $key = $this->askForKey(
            'Key?',
            $this->db->persons(),
            RecordUtility::createKey($firstname, $lastname)
        );
        $this->db->persons()->insert([
            'key' => $key,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'nationality' => $nationality,
        ]);
        return $key;
    }
}
