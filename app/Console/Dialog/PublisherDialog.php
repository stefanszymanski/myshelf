<?php

namespace App\Console\Dialog;

use App\Utility\RecordUtility;

class PublisherDialog extends AbstractRecordDialog
{
    /**
     * Build autocomplete options.
     */
    protected function buildAutocompleteOptions(): array
    {
        $records = $this->db->publishers()->createQueryBuilder()
            ->select(['key', 'name'])
            ->getQuery()
            ->fetch();
        $options = [];
        foreach ($records as $record) {
            $options[$record['name']] = $record['key'];
        }
        return $options;
    }

    /**
     * Parse the user input from the record selection into record default values.
     *
     * @param string $value The user input
     * @return array Record defaults
     */
    protected function getDefaultsFromInput(string $value): array
    {
        return [
            'name' => $value,
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
        $name = $this->askMandatory('Name?', $defaults['name'] ?? null);
        $defaultKey = RecordUtility::createKey($name);
        $key = $this->askForKey('Key?', $this->db->publishers(), $defaultKey);
        $this->db->publishers()->insert([
            'key' => $key,
            'name' => $name,
        ]);
        return $key;
    }
}
