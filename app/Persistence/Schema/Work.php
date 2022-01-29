<?php

namespace App\Persistence\Schema;

use App\Persistence\Data\FieldFactory as DataFieldFactory;
use App\Persistence\Database;
use App\Persistence\Query\FieldFactory as QueryFieldFactory;
use App\Persistence\Query\FilterFactory as QueryFilterFactory;
use App\Persistence\Table;
use SleekDB\Store;

class Work extends AbstractSchema
{
    /**
     * {@inheritDoc}
     */
    protected array $defaultListFields = ['id', 'title', 'authors'];

    /**
     * {@inheritDoc}
     */
    protected array $newRecordDialogFields = ['title', 'authors', 'published'];

    protected function configure(): void
    {
        $this->registerDataFields([
            // General information
            'title' => DataFieldFactory::string(label: 'Title', required: true),
            'language' => DataFieldFactory::select(Book::LANGUAGES, label: 'Language'),
            'orig_language' => DataFieldFactory::select(Book::ORIGINAL_LANGUAGES, label: 'Original Language'),
            'persons' => DataFieldFactory::struct(
                label: 'Persons',
                fields: [
                    'authors' => DataFieldFactory::references('person', label: 'Authors', sortable: true),
                    'translators' => DataFieldFactory::references('person', label: 'Translators', sortable: true),
                ]
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
        ]);

        $this->registerQueryFields([
            // General information
            'title' => QueryFieldFactory::forDatafield('title', label: 'Title'),
            'language' => QueryFieldFactory::forDatafield('language', label: 'Language'),
            'origlanguage' => QueryFieldFactory::forDatafield('orig_language', label: 'Original Language'),
            'persons' => QueryFieldFactory::forDatafield('persons', label: 'Persons'),
            'authors' => QueryFieldFactory::forDatafield('persons.authors', label: 'Authors'),
            'translators' => QueryFieldFactory::forDatafield('persons.translators', label: 'Translators'),
            'published' => QueryFieldFactory::forDatafield('published', label: 'Published'),
            'publisher' => QueryFieldFactory::forDatafield('published.publisher', label: 'Publisher'),
            'published.date' => QueryFieldFactory::forDatafield('published.date', label: 'Publishing Date'),
            'published.publisher' => QueryFieldFactory::forDatafield('published.publisher', label: 'Publisher'),
            'published.place' => QueryFieldFactory::forDatafield('published.place', label: 'Publishing place'),
        ]);

        $this->registerQueryFilters([
            // Simple data fields
            'title' => QueryFilterFactory::forField('title', equal: true, unequal: true, like: true),
            'language' => QueryFilterFactory::forField('language', equal: true, unequal: true, like: true),
            'origlanguage' => QueryFilterFactory::forField('origlanguage', equal: true, unequal: true, like: true),
            'published' => QueryFilterFactory::forField('published', equal: true, unequal: true, gt: true, lt: true, gte: true, lte: true),
            'author' => QueryFilterFactory::forReference('persons.authors', 'person', isMultivalue: true),
            'translator' => QueryFilterFactory::forReference('persons.translators', 'person', isMultivalue: true),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getRecordTitle(array $record, Table $table, Database $db): string
    {
        // Display "{title} <fg=gray>{first person}</>", append "et al." if there is more than one person.
        $titleParts = [];
        $titleParts[] = $record['data']['title'];
        $personIds = collect($record['data']['persons'] ?? [])->flatten()->unique()->all();
        if (!empty($personIds)) {
            $firstPersonId = array_shift($personIds);
            $titleParts[] = ' <fg=gray>';
            $titleParts[] = $db->persons()->getRecordTitle($firstPersonId);
            if (!empty($personIds)) {
                $titleParts[] = ' et al.';
            }
            $titleParts[] = '</>';
        }
        return implode('', $titleParts);
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
