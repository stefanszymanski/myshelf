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
            'orig_language' => DataFieldFactory::select(self::ORIGINAL_LANGUAGES, label: 'Original Language'),
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
            'printrun' => DataFieldFactory::integer(label: 'Print run'),
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
            'authors' => QueryFieldFactory::forDatafield('persons.authors', label: 'Authors'),
            'translators' => QueryFieldFactory::forDatafield('persons.translators', label: 'Translators'),
            'illustrators' => QueryFieldFactory::forDatafield('persons.illustrators', label: 'Illustrators'),
            'editors' => QueryFieldFactory::forDatafield('persons.editors', label: 'Editors'),
            // TODO add query field: reference count, e.g. number of authors
            /* 'authors._count' => QueryFieldFactory::count('authors', label: 'Authors #'), */
            'published' => QueryFieldFactory::forDatafield('published', label: 'Published'),
            'publisher' => QueryFieldFactory::forDatafield('published.publisher', label: 'Publisher'),
            'published.date' => QueryFieldFactory::forDatafield('published.date', label: 'Publishing Date'),
            'published.publisher' => QueryFieldFactory::forDatafield('published.publisher', label: 'Publisher'),
            'published.place' => QueryFieldFactory::forDatafield('published.place', label: 'Publishing place'),
            'isbn10' => QueryFieldFactory::forDatafield('isbn10', label: 'ISBN-10'),
            'isbn13' => QueryFieldFactory::forDatafield('isbn13', label: 'ISBN-13'),
            'content' => QueryFieldFactory::forDatafield('content', label: 'Content'),
            // TODO add query fields for book content
            // Information about a specific edition
            'binding' => QueryFieldFactory::forDatafield('binding', label: 'Binding'),
            'edition' => QueryFieldFactory::forDatafield('edition', label: 'Edition'),
            'printrun' => QueryFieldFactory::forDatafield('printrun', label: 'Print-run'),
            // Information about a specific copy
            'condition' => QueryFieldFactory::forDatafield('condition', label: 'Condition'),
            // TODO add query field: combined info of acquired fields
            'acquired.at' => QueryFieldFactory::forDatafield('acquired.at', label: 'Acquired at'),
            'acquired.from' => QueryFieldFactory::forDatafield('acquired.from', label: 'Acquired from'),
            'acquired.as' => QueryFieldFactory::forDatafield('acquired.as', label: 'Acquired as'),
        ]);

        $this->registerQueryFilters([
            // Simple data fields
            'title' => QueryFilterFactory::forField('title', equal: true, unequal: true, like: true),
            'language' => QueryFilterFactory::forField('language', equal: true, unequal: true, like: true),
            'origlanguage' => QueryFilterFactory::forField('origlanguage', equal: true, unequal: true, like: true),
            'binding' => QueryFilterFactory::forField('binding', equal: true, unequal: true, like: true),
            'published' => QueryFilterFactory::forField('published', equal: true, unequal: true, gt: true, lt: true, gte: true, lte: true),
            'isbn' => QueryFilterFactory::forField('isbn', equal: true, unequal: true, like: true),
            'acquired.at' => QueryFilterFactory::forField('acquired.at', equal: true, unequal: true, gt: true, lt: true, gte: true, lte: true),
            'acquired.from' => QueryFilterFactory::forField('acquired.from', equal: true, unequal: true, like: true),
            'acquired.as' => QueryFilterFactory::forField('acquired.as', equal: true, unequal: true, like: true),
            // Reference data fields
            'author' => QueryFilterFactory::forReference('authors', 'person', isMultivalue: true),
            'editor' => QueryFilterFactory::forReference('editors', 'person', isMultivalue: true),
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
