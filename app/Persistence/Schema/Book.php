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

    protected const EDITIONS = [
        'first' => 'First',
        'revised' => 'Revised',
        'updated' => 'Revised and updated',
    ];

    public const LANGUAGES = [
        'de' => 'German',
        'en' => 'English',
    ];

    public const ORIGINAL_LANGUAGES = [
        'de' => 'German',
        'de_DE' => 'German (Germany)',
        'de_AT' => 'German (Austria)',
        'de_CH' => 'German (Switzerland)',
        'en' => 'English',
        'en_GB' => 'English (British)',
        'en_US' => 'English (USA)',
        'fr' => 'French',
        'pl' => 'Polish',
        'no' => 'Norwegian',
        'nl' => 'Dutch',
        'ru' => 'Russian',
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
    protected array $newRecordDialogFields = ['title', 'authors', 'binding', 'published'];

    protected function configure(): void
    {
        $this->registerDataFields([
            // General information
            'title' => DataFieldFactory::string(label: 'Title', required: true),
            'language' => DataFieldFactory::select(self::LANGUAGES, label: 'Language'),
            'origlanguage' => DataFieldFactory::select(self::ORIGINAL_LANGUAGES, label: 'Original Language'),
            'persons' => DataFieldFactory::struct(
                label: 'Persons',
                fields: [
                    'authors' => DataFieldFactory::references('person', label: 'Authors', sortable: true),
                    'translators' => DataFieldFactory::references('person', label: 'Translators', sortable: true),
                    'illustrators' => DataFieldFactory::references('illustrators', label: 'Illustrators', sortable: true),
                    'editors' => DataFieldFactory::references('person', label: 'Editors', sortable: true),
                ],
            ),
            'published' => DataFieldFactory::struct(
                label: 'Published',
                fields: [
                    'date' => DataFieldFactory::looseDate(label: 'Date'),
                    'publisher' => DataFieldFactory::reference('publisher', label: 'Publisher'),
                    'place' => DataFieldFactory::string(label: 'Place'),
                ],
                // Format all three fields as one line.
                formatter: function (array $data, array $fields) {
                    return collect($fields)
                        ->mapWithKeys(fn ($field, $key) => [$key => $field->formatValue($data[$key] ?? null)])
                        ->filter()
                        ->implode(', ');
                },
            ),
            'isbn10' => DataFieldFactory::isbn10(label: 'ISBN-10'),
            'isbn13' => DataFieldFactory::isbn13(label: 'ISBN-13'),
            'content' => DataFieldFactory::references('work', label: 'Content', sortable: true),
            // Information about a specific edition
            'binding' => DataFieldFactory::select(self::BINDINGS, label: 'Binding', required: true),
            'edition' => DataFieldFactory::select(self::EDITIONS, label: 'Edition'),
            'printing' => DataFieldFactory::integer(label: 'Printing'),
            // Information about a specific copy
            'condition' => DataFieldFactory::select(self::CONDITIONS, label: 'Condition'),
            'acquired' => DataFieldFactory::struct(
                label: 'Acquired',
                fields: [
                    'date' => DataFieldFactory::looseDate(label: 'Date'),
                    'source' => DataFieldFactory::string(label: 'Source'),
                    'condition' => DataFieldFactory::select(self::CONDITIONS, label: 'Condition'),
                ],
            ),
        ]);

        $this->registerQueryFields([
            // General information
            'title' => QueryFieldFactory::forDatafield('title', label: 'Title'),
            'language' => QueryFieldFactory::forDatafield('language', label: 'Language'),
            'origlanguage' => QueryFieldFactory::forDatafield('origlanguage', label: 'Original Language'),
            'persons.authors' => QueryFieldFactory::forDatafield('persons.authors', label: 'Authors'),
            'person.translators' => QueryFieldFactory::forDatafield('persons.translators', label: 'Translators'),
            'person.illustrators' => QueryFieldFactory::forDatafield('persons.illustrators', label: 'Illustrators'),
            'person.editors' => QueryFieldFactory::forDatafield('persons.editors', label: 'Editors'),
            'authors' => QueryFieldFactory::alternatives(['persons.authors', 'content:persons.authors'], label: 'Authors'),
            'translators' => QueryFieldFactory::alternatives(['persons.translators', 'content:persons.translators'], label: 'Translators'),
            'illustrators' => QueryFieldFactory::alternatives(['persons.illustrators', 'content:persons.illustrators'], label: 'Illustrators'),
            'editors' => QueryFieldFactory::alternatives(['persons.editors', 'content:persons.editors'], label: 'Editors'),
            // TODO add query field: reference count, e.g. number of authors
            /* 'authors._count' => QueryFieldFactory::count('authors', label: 'Authors #'), */
            'published' => QueryFieldFactory::forDatafield('published', label: 'Published'),
            'publisher' => QueryFieldFactory::forDatafield('published.publisher', label: 'Publisher'),
            'published.date' => QueryFieldFactory::forDatafield('published.date', label: 'Publishing Date'),
            'published.publisher' => QueryFieldFactory::forDatafield('published.publisher', label: 'Publisher'),
            'published.place' => QueryFieldFactory::forDatafield('published.place', label: 'Publishing Place'),
            'isbn10' => QueryFieldFactory::forDatafield('isbn10', label: 'ISBN-10'),
            'isbn13' => QueryFieldFactory::forDatafield('isbn13', label: 'ISBN-13'),
            'content' => QueryFieldFactory::forDatafield('content', label: 'Content'),
            // TODO add query fields for book content
            // Information about a specific edition
            'binding' => QueryFieldFactory::forDatafield('binding', label: 'Binding'),
            'edition' => QueryFieldFactory::forDatafield('edition', label: 'Edition'),
            'printing' => QueryFieldFactory::forDatafield('printing', label: 'Printing'),
            // Information about a specific copy
            'condition' => QueryFieldFactory::forDatafield('condition', label: 'Condition'),
            'acquired' => QueryFieldFactory::forDatafield('acquired', label: 'Acquired'),
            'acquired.date' => QueryFieldFactory::forDatafield('acquired.date', label: 'Acquired at'),
            'acquired.source' => QueryFieldFactory::forDatafield('acquired.source', label: 'Acquired from'),
            'acquired.condition' => QueryFieldFactory::forDatafield('acquired.condition', label: 'Acquired as'),
        ]);

        $this->registerQueryFilters([
            // General information
            'title' => QueryFilterFactory::forField('title', equal: true, unequal: true, like: true),
            'language' => QueryFilterFactory::forField('language', equal: true, unequal: true, like: true),
            'origlanguage' => QueryFilterFactory::forField('origlanguage', equal: true, unequal: true, like: true),
            'author' => QueryFilterFactory::forReference('persons.authors', 'person', isMultivalue: true),
            'translator' => QueryFilterFactory::forReference('persons.translators', 'person', isMultivalue: true),
            'illustrator' => QueryFilterFactory::forReference('persons.illustrators', 'person', isMultivalue: true),
            'editor' => QueryFilterFactory::forReference('persons.editors', 'person', isMultivalue: true),
            'published.date' => QueryFilterFactory::forField('published.date', equal: true, unequal: true, gt: true, lt: true, gte: true, lte: true),
            'published.publisher' => QueryFilterFactory::forReference('published.publisher', 'publisher'),
            'published.place' => QueryFilterFactory::forField('published.place', equal: true, unequal: true, like: true),
            'isbn10' => QueryFilterFactory::forField('isbn10', equal: true, unequal: true, like: true),
            'isbn13' => QueryFilterFactory::forField('isbn13', equal: true, unequal: true, like: true),
            'content' => QueryFilterFactory::forReference('content', 'work', isMultivalue: true),
            // Information about a specific edition
            'binding' => QueryFilterFactory::forField('binding', equal: true, unequal: true, like: true),
            'edition' => QueryFilterFactory::forField('edition', equal: true, unequal: true, like: true),
            'printing' => QueryFilterFactory::forField('printing', equal: true, unequal: true, gt: true, lt: true, gte: true, lte: true),
            // Information about a specific copy
            'condition' => QueryFilterFactory::forField('condition', equal: true, unequal: true, like: true),
            'acquired.date' => QueryFilterFactory::forField('acquired.date', equal: true, unequal: true, gt: true, lt: true, gte: true, lte: true),
            'acquired.source' => QueryFilterFactory::forField('acquired.source', equal: true, unequal: true, like: true),
            'acquired.condition' => QueryFilterFactory::forField('acquired.condition', equal: true, unequal: true, like: true),
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
