<?php

namespace App\Domain\Type;

use App\Database;
use SleekDB\QueryBuilder;

class Book extends AbstractType
{
    protected function configure(): void
    {
        // Real fields
        $this
            ->registerField(
                name: 'title',
                label: 'Title',
                type: self::FIELD_TYPE_REAL,
            )
            ->registerField(
                name: 'published',
                label: 'Published',
                type: self::FIELD_TYPE_REAL,
            )
            ->registerField(
                name: 'acquired',
                label: 'Acquired',
                type: self::FIELD_TYPE_REAL,
            );

        // Joined fields
        $this
            ->registerField(
                name: 'publisher',
                label: 'Publisher',
                description: 'Name of the publisher',
                type: self::FIELD_TYPE_JOINED,
                queryModifier: function (QueryBuilder $qb, string $fieldName, Database $db) {
                    return $qb
                        ->join(fn ($book) => $db->publishers()->findBy(['key', '=', $book['publisher']]), '_publisher')
                        ->select([$fieldName => '_publisher.0.name']);
                },
            )
            ->registerField(
                name: 'authors',
                label: 'Authors',
                description: 'Names of the authors',
                type: self::FIELD_TYPE_JOINED,
                queryModifier: function (QueryBuilder $qb, string $fieldName, Database $db) {
                    return $qb
                        ->join(fn ($book) => $db->persons()->findBy(['key', 'IN', $book['authors']]), '_authors')
                        ->select([$fieldName => function ($book) {
                            $authors = array_map(fn ($author) => sprintf('%s %s', $author['firstname'], $author['lastname']), $book['_authors']);
                            return implode('; ', $authors);
                        }]);
                },
            );

        // TODO add a author variant, that creates a record for each author of a book. That would be useful for grouping by.
        //      Otherwise grouping by "authors" would create separate groups for books with multiple authors.

        // Filters on real fields
        $this
            ->registerSimpleFilter(
                name: 'author',
                operator: '=',
                description: 'Exact match on a authors key',
                queryModifier: fn ($value) => ['authors', 'CONTAINS', $value]
            )
            ->registerSimpleFilter(
                name: 'title',
                operator: '=',
                description: 'Exact match on the title',
                queryModifier: fn ($value) => ['title', '=', $value]
            )
            ->registerSimpleFilter(
                name: 'title',
                operator: '~',
                description: 'Pattern match on the title',
                queryModifier: fn ($value) => ['title', 'LIKE', $value]
            );

        // Filters on publishing year
        $operators = [
            ['=', '==', 'equal'],
            ['<', '<', 'less'],
            ['>', '>', 'greater'],
            ['<=', '<=', 'less or equal'],
            ['>=', '>=', 'greater or equal'],
        ];
        foreach ($operators as list($operator, $internalOperator, $description)) {
            $this->registerSimpleFilter(
                name: 'published',
                operator: $operator,
                description: "Publishing year $description",
                queryModifier: fn ($value) => ['published', $internalOperator, $value],
            );
        }

        // Joined filters on persons (authors and editors)
        $personStore = fn (Database $db) => $db->getStore('persons');
        $authorCriteria = fn (array $book) => ['key', 'IN', $book['authors']];
        $editorCriteria = fn (array $book) => ['key', 'IN', $book['editors']];
        $persons = [
            ['author', 'an authors', $authorCriteria],
            ['editor', 'an editors', $editorCriteria],
        ];
        foreach ($persons as list($field, $description, $criteria)) {
            $this
                ->registerJoinedStoreFilter(
                    name: "$field.lastname",
                    operator: '=',
                    description: "Exact match on $description last name",
                    foreignStore: $personStore,
                    foreignCriteria: $criteria,
                    foreignField: 'lastname',
                    foreignOperator: '=',
                )
                ->registerJoinedStoreFilter(
                    name: "$field.lastname",
                    operator: '~',
                    description: "Pattern match on $description last name",
                    foreignStore: $personStore,
                    foreignCriteria: $criteria,
                    foreignField: 'lastname',
                    foreignOperator: 'LIKE',
                )
                ->registerJoinedStoreFilter(
                    name: "$field.name",
                    operator: '~',
                    description: "Pattern match on $description full name",
                    foreignStore: $personStore,
                    foreignCriteria: $criteria,
                    foreignField: fn (array $author) => sprintf('%s %s', $author['firstname'], $author['lastname']),
                    foreignOperator: 'LIKE',
                );
        }

        // Joined filters on publisher
        $publisherStore = fn (Database $db) => $db->getStore('publishers');
        $publisherCriteria = fn (array $book) => ['key', '=', $book['publisher']];
        $this
            ->registerJoinedStoreFilter(
                name: 'publisher.name',
                operator: '=',
                description: 'Exact match on the publishers name',
                foreignStore: $publisherStore,
                foreignCriteria: $publisherCriteria,
                foreignField: 'name',
                foreignOperator: '='
            )
            ->registerJoinedStoreFilter(
                name: 'publisher.name',
                operator: '~',
                description: 'Pattern match on the publishers name',
                foreignStore: $publisherStore,
                foreignCriteria: $publisherCriteria,
                foreignField: 'name',
                foreignOperator: 'LIKE'
            );
    }
}
