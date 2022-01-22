<?php

namespace App\Persistence\Schema;

use App\Persistence\Data\FieldFactory as DataFieldFactory;
use App\Persistence\Query\FieldFactory as QueryFieldFactory;
use App\Persistence\Query\FilterFactory as QueryFilterFactory;
use SleekDB\Store;

class Publisher extends AbstractSchema
{
    /**
     * {@inheritDoc}
     */
    protected array $recordTitleFields = ['shortname', 'name'];

    /**
     * {@inheritDoc}
     */
    protected array $defaultListFields = ['id', 'name'];

    /**
     * {@inheritDoc}
     */
    protected array $newRecordDialogFields = ['name', 'shortname'];

    protected function configure(): void
    {
        $this->registerDataFields([
            'name' => DataFieldFactory::string(label: 'Name', required: true),
            'shortname' => DataFieldFactory::string(label: 'Short name')
        ]);

        $this->registerQueryFields([
            'name' => QueryFieldFactory::datafield('name', label: 'Name'),
            'shortname' => QueryFieldFactory::datafield('shortname', label: 'Short name'),
            'books' => QueryFieldFactory::countReferences('book', 'publisher', label: 'Books'),
        ]);

        $this->registerQueryFilters([
            'name' => QueryFilterFactory::forField('name', equal: true, like: true),
            'shortname' => QueryFilterFactory::forField('shortname', equal: true, like: true),
            'books' => QueryFilterFactory::forField('books', equal: true, unequal: true, gt: true, lt: true, gte: true, lte: true),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getAutocompleteOptions(Store $store): array
    {
        $records = $store->createQueryBuilder()
            ->select(['id', 'data.name'])
            ->getQuery()
            ->fetch();
        $options = [];
        foreach ($records as $record) {
            $options[$record['data']['name']] = $record['id'];
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
                'name' => $value,
            ],
        ];
    }
}
