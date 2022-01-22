<?php

namespace App\Persistence\Schema;

use App\Persistence\Data\FieldFactory as DataFieldFactory;
use App\Persistence\Query\FieldFactory as QueryFieldFactory;
use App\Persistence\Query\FilterFactory as QueryFilterFactory;
use SleekDB\Store;

class Book extends AbstractSchema
{
    // TODO add more bindings
    protected const BINDINGS = [
        'hardcover' => 'Hardcover',
        'hardcover-jacket' => 'Hardcover with dust jacket',
        'paperback' => 'Paperback',
        'paperback-jacket' => 'Paperback with dust jacket',
        'other' => 'Other',
    ];

    // TODO add more conditions
    protected const CONDITIONS = [
        'new' => 'New',
        'used' => 'Used',
    ];

    /**
     * {@inheritDoc}
     */
    protected array $recordTitleFields = ['title'];

    /**
     * {@inheritDoc}
     */
    protected array $defaultListFields = ['id', 'title', 'authors'];

    /**
     * {@inheritDoc}
     */
    protected array $newRecordDialogFields = ['title', 'authors', 'binding', 'publisher', 'published'];

    protected function configure(): void
    {
        $this->registerDataFields([
            'title' => DataFieldFactory::string(label: 'Title', required: true),
            'authors' => DataFieldFactory::references('person', label: 'Authors', sortable: true),
            'editors' => DataFieldFactory::references('person', label: 'Editors', sortable: true),
            'binding' => DataFieldFactory::select(self::BINDINGS, label: 'Binding', required: true),
            'publisher' => DataFieldFactory::reference('publisher', label: 'Publisher'),
            'published' => DataFieldFactory::integer(label: 'Published', minimum: -2000, maximum: 3000),
            'isbn' => DataFieldFactory::isbn(label: 'ISBN'),
            'acquired' => DataFieldFactory::struct([
                'at' => DataFieldFactory::looseDate(label: 'At'),
                'from' => DataFieldFactory::string(label: 'From'),
                'as' => DataFieldFactory::select(self::CONDITIONS, label: 'As'),
            ], label: 'Acquired'),
        ]);

        $this->registerQueryFields([
            'title' => QueryFieldFactory::datafield('title', label: 'Title'),
            'authors' => QueryFieldFactory::datafield('authors', label: 'Authors'),
            'editors' => QueryFieldFactory::datafield('editors', label: 'Editors'),
            'binding' => QueryFieldFactory::datafield('binding', label: 'Binding'),
            'publisher' => QueryFieldFactory::datafield('publisher', label: 'Publisher'),
            'published' => QueryFieldFactory::datafield('published', label: 'Published'),
            'isbn' => QueryFieldFactory::datafield('isbn', label: 'ISBN'),
            'acquired.at' => QueryFieldFactory::datafield('acquired.at', label: 'Acquired at'),
            'acquired.from' => QueryFieldFactory::datafield('acquired.from', label: 'Acquired from'),
            'acquired.as' => QueryFieldFactory::datafield('acquired.as', label: 'Acquired as'),
            // TODO add query field: reference count
            /* 'authors._count' => QueryFieldFactory::count('authors', label: 'Authors #'), */
            // TODO add query field: combined info of acquired fields
        ]);

        $this->registerQueryFilters([
            // Simple data fields
            'title' => QueryFilterFactory::forField('title', equal: true, unequal: true, like: true),
            'binding' => QueryFilterFactory::forField('binding', equal: true, unequal: true, like: true),
            'published' => QueryFilterFactory::forField('published', equal: true, unequal: true, gt: true, lt: true, gte: true, lte: true),
            'isbn' => QueryFilterFactory::forField('isbn', equal: true, unequal: true, like: true),
            'acquired.at' => QueryFilterFactory::forField('acquired.at', equal: true, unequal: true, gt: true, lt: true, gte: true, lte: true),
            'acquired.from' => QueryFilterFactory::forField('acquired.from', equal: true, unequal: true, like: true),
            'acquired.as' => QueryFilterFactory::forField('acquired.as', equal: true, unequal: true, like: true),
            // Reference data fields
            'author' => QueryFilterFactory::forReference('authors', 'person', isMultivalue: true),
            'editor' => QueryFilterFactory::forReference('editors', 'person', isMultivalue: true),
            'publisher' => QueryFilterFactory::forReference('publisher', 'publisher'),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getAutocompleteOptions(Store $store): array
    {
        $records = $store->createQueryBuilder()
            ->select(['key', 'data.title'])
            ->getQuery()
            ->fetch();
        $options = [];
        foreach ($records as $record) {
            $options[$record['data']['title']] = $record['id'];
        }
        return $options;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultsFromAutocompleteInput(string $value): array
    {
        return [
            'data' => [
                'title' => $value,
            ],
        ];
    }
}
