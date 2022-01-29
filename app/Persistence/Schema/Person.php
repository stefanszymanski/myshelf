<?php

namespace App\Persistence\Schema;

use App\Persistence\Data\FieldFactory as DataFieldFactory;
use App\Persistence\Database;
use App\Persistence\Query\FieldFactory as QueryFieldFactory;
use App\Persistence\Query\FilterFactory as QueryFilterFactory;
use App\Persistence\Table;
use SleekDB\Store;

class Person extends AbstractSchema
{
    /**
     * {@inheritDoc}
     */
    protected array $defaultListFields = ['id', 'name'];

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->registerDataFields([
            'firstname' => DataFieldFactory::string(label: 'First name'),
            'lastname' => DataFieldFactory::string(label: 'Last name', required: true),
            'nationality' => DataFieldFactory::string(label: 'Nationality'),
        ]);

        $this->registerQueryFields([
            // Data fields
            'firstname' => QueryFieldFactory::forDatafield('firstname', label: 'First name'),
            'lastname' => QueryFieldFactory::forDatafield('lastname', label: 'Last name'),
            'nationality' => QueryFieldFactory::forDatafield('nationality', label: 'Nationality'),
            // Joined fields
            'name' => QueryFieldFactory::concat(', ', ['lastname', 'firstname'], label: 'Full name'),
            'name2' => QueryFieldFactory::concat(' ', ['firstname', 'lastname'], label: 'Full name'),
            // Person references
            'books' => QueryFieldFactory::countReferences('book', 'authors', label: 'Books'),
        ]);

        $this->registerQueryFilters([
            // Data fields
            'firstname' => QueryFilterFactory::forField('firstname', equal: true, like: true),
            'lastname' => QueryFilterFactory::forField('lastname', equal: true, like: true),
            'nationality' => QueryFilterFactory::forField('nationality', equal: true),
            // Person references
            'books' => QueryFilterFactory::forField('books', equal: true, unequal: true, gt: true, lt: true, gte: true, lte: true)
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getRecordTitle(array $record, Table $table, Database $db): string
    {
        return trim(sprintf('%s %s', $record['data']['firstname'], $record['data']['lastname']));
    }

    /**
     * Get autocomplete options for a record selection dialog.
     *
     * Fetches all persons and build two autocomplete options for each:
     * {firstname} {lastname}
     * {lastname}, {firstname}
     *
     * {@inheritDoc}
     */
    public function getAutocompleteOptions(Store $store): array
    {
        $records = $store->createQueryBuilder()
            ->select(['id', 'data'])
            ->getQuery()
            ->fetch();
        $options = [];
        foreach ($records as $record) {
            $options["{$record['data']['firstname']} {$record['data']['lastname']}"] = $record['id'];
            $options["{$record['data']['lastname']}, {$record['data']['firstname']}"] = $record['id'];
        }
        return $options;
    }

    /**
     * Splits the user input into firstname and lastname.
     *
     * {@inheritDoc}
     */
    public function getDefaultsFromAutocompleteInput(string $value): array
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
            'data' => [
                'firstname' => $firstname,
                'lastname' => $lastname,
            ],
        ];
    }
}
