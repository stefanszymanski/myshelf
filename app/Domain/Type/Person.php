<?php

namespace App\Domain\Type;

use App\Database;
use SleekDB\QueryBuilder;

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
}
