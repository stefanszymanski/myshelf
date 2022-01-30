<?php

namespace App\Persistence\Schema;

use App\Persistence\Data\FieldFactory as DataFieldFactory;
use App\Persistence\Query\FieldFactory as QueryFieldFactory;
use App\Persistence\Query\FilterFactory as QueryFilterFactory;
use SleekDB\Store;

class BookList extends AbstractSchema
{
    /**
     * {@inheritDoc}
     */
    protected array $recordTitleFields = ['name'];

    /**
     * {@inheritDoc}
     */
    protected array $defaultListFields = ['id', 'name'];

    /**
     * {@inheritDoc}
     */
    protected array $newRecordDialogFields = ['name', 'items'];

    protected function configure(): void
    {
        $this->registerDataFields([
            'name' => DataFieldFactory::string(label: 'Name', required: true),
            'items' => DataFieldFactory::references('book', sortable: true, label: 'Items'),
        ]);

        $this->registerQueryFields([
            'name' => QueryFieldFactory::forDatafield('name', label: 'Name'),
            'items' => QueryFieldFactory::forDatafield('items', label: 'Items'),
        ]);

        $this->registerQueryFilters([
            'name' => QueryFilterFactory::forField('name', equal: true, unequal: true, like: true),
            'items' => QueryFilterFactory::forReference('items', 'book', isMultivalue: true),
        ]);
    }

    /**
     * Build autocomplete options.
     */
    public function getAutocompleteOptions(Store $store): array
    {
        $records = $store->createQueryBuilder()
            ->select(['data.name'])
            ->getQuery()
            ->fetch();
        $options = [];
        foreach ($records as $record) {
            $options[$record['data']['name']] = $record['data']['id'];
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
