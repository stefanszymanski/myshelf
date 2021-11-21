<?php

namespace App\Console\Dialog;

use App\Utility\RecordUtility;
use App\Validator\IntegerValidator;
use App\Validator\LooseDateValidator;

class BookDialog extends AbstractRecordDialog
{
    /**
     * Build autocomplete options.
     */
    protected function buildAutocompleteOptions(): array
    {
        $records = $this->db->books()->createQueryBuilder()
            ->select(['key', 'title'])
            ->getQuery()
            ->fetch();
        $options = [];
        foreach ($records as $record) {
            $options[$record['title']] = $record['key'];
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
            'title' => $value,
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
        $title = $this->askMandatory('Title?', $defaults['title'] ?? null);

        $personDialog = new PersonDialog($this->db, $this->input, $this->output, $this->verbosity);
        $authors = $personDialog->askForMultiple('author');
        $editors = $personDialog->askForMultiple('editor');

        $publisherDialog = new PublisherDialog($this->db, $this->input, $this->output, $this->verbosity);
        $publisher = $publisherDialog->askForOne('publisher', $defaults['publisher'] ?? null);

        $published = $this->askWithValidation('Published?', [new IntegerValidator], $default['published'] ?? null);
        $acquired = $this->askWithValidation('Acquired?', [new LooseDateValidator], $defaults['acquired'] ?? null);

        // TODO store for lists
        // TODO fields: condition, list.id, list.volume

        $defaultKey = RecordUtility::createKey($authors[0] ?? $editors[0] ?? null, $title);
        $key = $this->askForKey('Key?', $this->db->books(), $defaultKey);

        $this->db->books()->insert([
            'key' => $key,
            'title' => $title,
            'authors' => $authors,
            'editors' => $editors,
            'publisher' => $publisher,
            'published' => $published,
            'acquired' => $acquired,
        ]);

        return $key;
    }
}
