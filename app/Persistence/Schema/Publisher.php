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
    protected array $recordTitleFields = ['fullname', 'shortname'];

    /**
     * {@inheritDoc}
     */
    protected array $defaultListFields = ['id', 'name'];

    /**
     * {@inheritDoc}
     */
    protected array $newRecordDialogFields = ['fullname', 'shortname'];

    protected function configure(): void
    {
        $this->registerDataFields([
            'fullname' => DataFieldFactory::string(label: 'Full Name', required: true),
            'shortname' => DataFieldFactory::string(label: 'Short name')
        ]);

        $this->registerQueryFields([
            'fullname' => QueryFieldFactory::forDatafield('fullname', label: 'Full Name'),
            'shortname' => QueryFieldFactory::forDatafield('shortname', label: 'Short name'),
            'name' => QueryFieldFactory::alternatives(['shortname', 'fullname'], 'Name'),
            'books' => QueryFieldFactory::countReferences('book', 'published.publisher', label: 'Books'),
        ]);

        $this->registerQueryFilters([
            'name' => QueryFilterFactory::forField('name', equal: true, like: true),
            'fullname' => QueryFilterFactory::forField('fullname', equal: true, like: true),
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
            ->select(['id', 'data.fullname'])
            ->getQuery()
            ->fetch();
        $options = [];
        foreach ($records as $record) {
            $options[$record['data']['fullname']] = $record['id'];
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
                'fullname' => $value,
            ],
        ];
    }
}
