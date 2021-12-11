<?php

namespace App\Persistence\Schema;

use App\Persistence\Database;
use App\Persistence\Query\FieldType as QueryFieldType;
use App\Utility\RecordUtility;
use App\Validator\IntegerValidator;
use App\Validator\IsbnValidator;
use App\Validator\LooseDateValidator;
use SleekDB\QueryBuilder;
use SleekDB\Store;
use Symfony\Component\Console\Question\ChoiceQuestion;

class Book extends AbstractSchema
{
    // TODO use labels for fields with hardcoded values
    protected const BINDINGS = [
        'Hardcover',
        'Hardcover with dust jacket',
        'Paperback',
        'Paperback with dust jacket',
        'Other',
    ];

    protected const CONDITIONS = [
        'new',
        'used'
    ];

    /**
     * {@inheritDoc}
     */
    protected array $defaultListFields = ['title', 'authors'];

    /**
     * {@inheritDoc}
     */
    public function createKeyForRecord(array $record): string
    {
        return RecordUtility::createKey($record['authors'][0] ?? $record['editors'][0] ?? $record['publisher'], $record['title']);
    }

    protected function configureFields(): void
    {
        $this
            ->registerField(
                name: 'title',
                label: 'Title',
                required: true,
            )
            ->registerReferenceField(
                name: 'authors',
                foreignTable: 'person',
                multiple: true,
                sortable: true,
                label: 'Authors',
                elementLabel: 'Author',
            )
            ->registerReferenceField(
                name: 'editors',
                foreignTable: 'person',
                multiple: true,
                sortable: true,
                label: 'Editors',
                elementLabel: 'Editor',
            )
            ->registerField(
                name: 'binding',
                label: 'Binding',
                question: fn ($value) => new ChoiceQuestion('Select a binding', static::BINDINGS, $value),
            )
            ->registerReferenceField(
                name: 'publisher',
                foreignTable: 'publisher',
                label: 'Publisher',
            )
            ->registerField(
                name: 'published',
                label: 'Published',
                validators: fn () => new IntegerValidator,
            )
            ->registerField(
                name: 'isbn',
                label: 'ISBN',
                validators: fn () => new IsbnValidator,
            );

        $this
            ->registerStructField(
                name: 'acquired',
                label: 'Acquired',
            )->addField(
                name: 'at',
                label: 'Date',
                validators: fn () => new LooseDateValidator,
            )->addField(
                name: 'from',
                label: 'From',
            )->addField(
                // TODO define more conditions
                name: 'as',
                label: 'Condition',
                question: fn ($value) => new ChoiceQuestion('Select a condition', static::CONDITIONS, $value),
            );
    }

    protected function configure(): void
    {
        $this->configureFields();

        // Real fields
        $this
            ->registerQueryField(
                name: 'title',
                label: 'Title',
                type: QueryFieldType::Real,
            )
            ->registerQueryField(
                name: 'published',
                label: 'Published',
                type: QueryFieldType::Real,
            )
            ->registerQueryField(
                name: 'binding',
                label: 'Binding',
                type: QueryFieldType::Real,
            )
            ->registerQueryField(
                name: 'isbn',
                label: 'ISBN',
                type: QueryFieldType::Real,
            )
            ->registerQueryField(
                name: 'acquired.at',
                label: 'Acquired at',
                type: QueryFieldType::Real,
            )
            ->registerQueryField(
                name: 'acquired.from',
                label: 'Acquired from',
                type: QueryFieldType::Real,
            )
            ->registerQueryField(
                name: 'acquired.as',
                label: 'Acquired as',
                type: QueryFieldType::Real,
            );

        // Joined fields
        $this
            ->registerQueryField(
                name: 'publisher',
                label: 'Publisher',
                description: 'Name of the publisher',
                type: QueryFieldType::Joined,
                queryModifier: function (QueryBuilder $qb, string $fieldName, Database $db) {
                    $publisherStore = $db->publishers()->store;
                    return $qb
                        ->join(fn ($book) => $publisherStore->findBy(['key', '=', $book['publisher']]), '_publisher')
                        ->select([$fieldName => '_publisher.0.name']);
                },
            )
            ->registerQueryField(
                name: 'authors',
                label: 'Authors',
                description: 'Names of the authors',
                type: QueryFieldType::Joined,
                queryModifier: function (QueryBuilder $qb, string $fieldName, Database $db) {
                    $personStore = $db->persons()->store;
                    return $qb
                        ->join(fn ($book) => $personStore->findBy(['key', 'IN', $book['authors']]), '_authors')
                        ->select([$fieldName => function ($book) {
                            $authors = array_map(fn ($author) => sprintf('%s %s', $author['firstname'], $author['lastname']), $book['_authors']);
                            return implode('; ', $authors);
                        }]);
                },
            )
            ->registerQueryField(
                name: 'editors',
                label: 'Editors',
                description: 'Names of the editors',
                type: QueryFieldType::Joined,
                queryModifier: function (QueryBuilder $qb, string $fieldName, Database $db) {
                    $personStore = $db->persons()->store;
                    return $qb
                        ->join(fn ($book) => $personStore->findBy(['key', 'IN', $book['editors']]), '_editors')
                        ->select([$fieldName => function ($book) {
                            $editors = array_map(fn ($editor) => sprintf('%s %s', $editor['firstname'], $editor['lastname']), $book['_editors']);
                            return implode('; ', $editors);
                        }]);
                },
            );

        // TODO add a author variant, that creates a record for each author of a book. That would be useful for grouping by.
        //      Otherwise grouping by "authors" would create separate groups for books with multiple authors.

        // Filters on real fields
        $this
            ->registerSimpleQueryFilter(
                field: 'author',
                operator: '=',
                description: 'Exact match on a authors key',
                queryModifier: fn ($value) => ['authors', 'CONTAINS', $value]
            )
            ->registerSimpleQueryFilter(
                field: 'title',
                operator: '=',
                description: 'Exact match on the title',
                queryModifier: fn ($value) => ['title', '=', $value]
            )
            ->registerSimpleQueryFilter(
                field: 'title',
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
            $this->registerSimpleQueryFilter(
                field: 'published',
                operator: $operator,
                description: "Publishing year $description",
                queryModifier: fn ($value) => ['published', $internalOperator, $value],
            );
        }

        // Filters on persons, i.e. authors and editors (join the person store)
        $personStore = fn (Database $db) => $db->persons()->store;
        $authorCriteria = fn (array $book) => ['key', 'IN', $book['authors']];
        $editorCriteria = fn (array $book) => ['key', 'IN', $book['editors']];
        $persons = [
            ['author', 'an authors', $authorCriteria],
            ['editor', 'an editors', $editorCriteria],
        ];
        foreach ($persons as list($field, $description, $criteria)) {
            $this
                ->registerJoinedStoreQueryFilter(
                    field: "$field.lastname",
                    operator: '=',
                    description: "Exact match on $description last name",
                    foreignStore: $personStore,
                    foreignCriteria: $criteria,
                    foreignField: 'lastname',
                    foreignOperator: '=',
                )
                ->registerJoinedStoreQueryFilter(
                    field: "$field.lastname",
                    operator: '~',
                    description: "Pattern match on $description last name",
                    foreignStore: $personStore,
                    foreignCriteria: $criteria,
                    foreignField: 'lastname',
                    foreignOperator: 'LIKE',
                )
                ->registerJoinedStoreQueryFilter(
                    field: "$field.name",
                    operator: '~',
                    description: "Pattern match on $description full name",
                    foreignStore: $personStore,
                    foreignCriteria: $criteria,
                    foreignField: fn (array $author) => sprintf('%s %s', $author['firstname'], $author['lastname']),
                    foreignOperator: 'LIKE',
                );
        }

        // Filters on publisher (join the publisher store)
        $publisherStore = fn (Database $db) => $db->publishers()->store;
        $publisherCriteria = fn (array $book) => ['key', '=', $book['publisher']];
        $this
            ->registerJoinedStoreQueryFilter(
                field: 'publisher.name',
                operator: '=',
                description: 'Exact match on the publishers name',
                foreignStore: $publisherStore,
                foreignCriteria: $publisherCriteria,
                foreignField: 'name',
                foreignOperator: '='
            )
            ->registerJoinedStoreQueryFilter(
                field: 'publisher.name',
                operator: '~',
                description: 'Pattern match on the publishers name',
                foreignStore: $publisherStore,
                foreignCriteria: $publisherCriteria,
                foreignField: 'name',
                foreignOperator: 'LIKE'
            );
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultListFields(): array
    {
        return ['title', 'authors'];
    }

    /**
     * {@inheritDoc}
     */
    public function getAutocompleteOptions(Store $store): array
    {
        $records = $store->createQueryBuilder()
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
     * {@inheritDoc}
     */
    public function getDefaultsFromAutocompleteInput(string $value): array
    {
        return [
            'title' => $value,
        ];
    }
}
