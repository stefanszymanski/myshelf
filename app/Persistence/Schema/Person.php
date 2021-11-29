<?php

namespace App\Persistence\Schema;

use App\Persistence\Database;
use App\Persistence\FieldType;
use SleekDB\QueryBuilder;
use SleekDB\Store;

class Person extends AbstractSchema
{
    /**
     * {@inheritDoc}
     */
    protected array $defaultListFields = ['name'];

    /**
     * {@inheritDoc}
     */
    protected array $keyFields = ['firstname', 'lastname'];

    protected function configure(): void
    {
        // Register data fields
        $this
            ->registerField('firstname', 'First name', false)
            ->registerField('lastname', 'Last name', true)
            ->registerField('nationality', 'Nationality', false);

        // TODO more join fields: books-as-editor, publishers, publishers-as-editor

        // Real fields
        $this
            ->registerQueryField(
                name: 'firstname',
                label: 'First name',
                type: FieldType::Real,
            )
            ->registerQueryField(
                name: 'lastname',
                label: 'Last name',
                type: FieldType::Real,
            )
            ->registerQueryField(
                name: 'nationality',
                label: 'Nationality',
                type: FieldType::Real,
            );

        // Virtual fields made of fields from the same record
        $this
            ->registerQueryField(
                name: 'name',
                label: 'Full name',
                type: FieldType::Virtual,
                description: 'Last name and first name concatenated: `{lastname}, {firstname}`',
                queryModifier: function (QueryBuilder $qb, string $fieldName) {
                    return $qb->select([$fieldName => ['CONCAT' => [', ', 'lastname', 'firstname']]]);
                },
            )
            ->registerQueryField(
                name: 'name2',
                label: 'Full name',
                type: FieldType::Virtual,
                description: 'First name and last name concatenated: `{firstname} {lastname}`',
                queryModifier: function (QueryBuilder $qb, string $fieldName) {
                    return $qb->select([$fieldName => ['CONCAT' => [' ', 'firstname', 'lastname']]]);
                },
            );

        // Joined fields
        $this
            ->registerQueryField(
                name: 'books',
                label: 'Books',
                type: FieldType::Joined,
                description: 'Number of books the person is an author of',
                queryModifier: function (QueryBuilder $qb, string $fieldName, Database $db) {
                    $bookStore = $db->books()->store;
                    return $qb
                        ->join(fn ($person) => $bookStore->findBy(['authors', 'CONTAINS', $person['key']]), '_books')
                        ->select([$fieldName => ['LENGTH' => '_books']]);
                }
            );

        // Filters on real fields
        $this
            ->registerSimpleFilter(
                field: 'firstname',
                operator: '=',
                description: 'Exact match on first name',
                queryModifier: fn ($value) => ['firstname', '=', $value],
            )
            ->registerSimpleFilter(
                field: 'firstname',
                operator: '~',
                description: 'Pattern match on first name',
                queryModifier: fn ($value) => ['firstname', 'LIKE', $value],
            )
            ->registerSimpleFilter(
                field: 'lastname',
                operator: '=',
                description: 'Exact match on last name',
                queryModifier: fn ($value) => ['lastname', '=', $value],
            )
            ->registerSimpleFilter(
                field: 'lastname',
                operator: '~',
                description: 'Pattern match on last name',
                queryModifier: fn ($value) => ['lastname', 'LIKE', $value],
            )
            ->registerSimpleFilter(
                field: 'nationality',
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
                field: 'books',
                operator: $operator,
                description: "Number of books authored $description",
                foreignStore: fn (Database $db) => $db->books()->store,
                foreignCriteria: fn (array $person) => ['authors', 'CONTAINS', $person['key']],
                foreignValue: fn (array $books) => count($books),
                foreignOperator: $foreignOperator,
            );
        }
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
            'firstname' => $firstname,
            'lastname' => $lastname,
        ];
    }
}
