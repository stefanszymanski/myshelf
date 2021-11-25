<?php

namespace App\Domain\Type;

use App\Database;
use SleekDB\QueryBuilder;
use SleekDB\Store;

class Person extends AbstractType
{
    protected function configure(): void
    {
        // TODO more join fields: books-as-editor, publishers, publishers-as-editor

        // Real fields
        $this
            ->registerField(
                name: 'firstname',
                label: 'First name',
                type: self::FIELD_TYPE_REAL,
            )
            ->registerField(
                name: 'lastname',
                label: 'Last name',
                type: self::FIELD_TYPE_REAL,
            )
            ->registerField(
                name: 'nationality',
                label: 'Nationality',
                type: self::FIELD_TYPE_REAL,
            );

        // Virtual fields made of fields from the same record
        $this
            ->registerField(
                name: 'name',
                label: 'Full name',
                type: self::FIELD_TYPE_VIRTUAL,
                description: 'Last name and first name concatenated: `{lastname}, {firstname}`',
                queryModifier: function (QueryBuilder $qb, string $fieldName) {
                    return $qb->select([$fieldName => ['CONCAT' => [', ', 'lastname', 'firstname']]]);
                }
            )
            ->registerField(
                name: 'name2',
                label: 'Full name',
                type: self::FIELD_TYPE_VIRTUAL,
                description: 'First name and last name concatenated: `{firstname} {lastname}`',
                queryModifier: function (QueryBuilder $qb, string $fieldName) {
                    return $qb->select([$fieldName => ['CONCAT' => [' ', 'firstname', 'lastname']]]);
                }
            );

        // Joined fields
        $this
            ->registerField(
                name: 'books',
                label: 'Books',
                type: self::FIELD_TYPE_JOINED,
                description: 'Number of books the person is an author of',
                queryModifier: function (QueryBuilder $qb, string $fieldName, Database $db) {
                    return $qb
                        ->join(fn ($person) => $db->books()->findBy(['authors', 'CONTAINS', $person['key']]), '_books')
                        ->select([$fieldName => ['LENGTH' => '_books']]);
                }
            );

        // Filters on real fields
        $this
            ->registerSimpleFilter(
                name: 'firstname',
                operator: '=',
                description: 'Exact match on first name',
                queryModifier: fn ($value) => ['firstname', '=', $value],
            )
            ->registerSimpleFilter(
                name: 'firstname',
                operator: '~',
                description: 'Pattern match on first name',
                queryModifier: fn ($value) => ['firstname', 'LIKE', $value],
            )
            ->registerSimpleFilter(
                name: 'lastname',
                operator: '=',
                description: 'Exact match on last name',
                queryModifier: fn ($value) => ['lastname', '=', $value],
            )
            ->registerSimpleFilter(
                name: 'lastname',
                operator: '~',
                description: 'Pattern match on last name',
                queryModifier: fn ($value) => ['lastname', 'LIKE', $value],
            )
            ->registerSimpleFilter(
                name: 'nationality',
                operator: '=',
                description: 'Exact match on nationality',
                queryModifier: fn ($value) => ['nationality', '=', $value],
            );

        // Filters on number of authored books (join the book store)
        $operators = [
            ['=', '==', 'equal'],
            ['<', '<', 'less'],
            ['>', '>', 'greater'],
            ['<=', '<=', 'less or equal'],
            ['>=', '>=', 'greater or equal'],
        ];
        foreach ($operators as list($operator, $foreignOperator, $description)) {
            $this->registerJoinedStoreFilter2(
                name: 'books',
                operator: $operator,
                description: "Number of books authored $description",
                foreignStore: fn (Database $db) => $db->getStore('books'),
                foreignCriteria: fn (array $person) => ['authors', 'CONTAINS', $person['key']],
                foreignValue: fn (array $books) => count($books),
                foreignOperator: $foreignOperator,
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultListFields(): array
    {
        return ['name'];
    }

    /**
     * Build autocomplete options.
     *
     * Fetches all persons and build two autocomplete options for each:
     * {firstname} {lastname}
     * {lastname}, {firstname}
     */
    public function getAutocompleteOptions(Store $store): array
    {
        $records = $store->createQueryBuilder()
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
     * Parse the user input from a record selection into record default values.
     *
     * Splits the input into firstname and lastname.
     *
     * @param string $value The user input
     * @return array Record defaults
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
            'firstname' => $firstname,
            'lastname' => $lastname,
        ];
    }
}
