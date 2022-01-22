<?php

namespace App\Persistence\Schema;

use SleekDB\Store;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Collection extends AbstractSchema
{
    protected array $keyFields = ['name'];

    protected function configure(): void
    {
        /*
         * When querying collections the query builder must know the target table
         * For querying the elements of a collection with a sorting, it must be known in which struct field to look for the sorting value.
         * e.g. SELECT * FROM book WHERE collections.*._collection = {queriedKey} ORDERBY collections.*._sorting
         *
         */
        $this
            ->registerField(
                name: 'name',
                label: 'Full name',
                required: true
            )
            ->registerField(
                name: 'targetTable',
                label: 'Target table',
                required: true
            )
            ->registerField(
                name: 'targetField',
                label: 'target Field',
                required: true,
            )
            ->registerField(
                name: 'sortable',
                label: 'Is sortable',
                required: true,
                question: fn (bool $defaultAnswer) => new ConfirmationQuestion('Is the collection sortable?', $defaultAnswer),
                validators: fn () => new BooleanValidator,
            );
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultListFields(): array
    {
        return ['name'];
    }

    /**
     * Build autocomplete options.
     */
    public function getAutocompleteOptions(Store $store): array
    {
        $records = $store->createQueryBuilder()
            ->select(['key', 'name'])
            ->getQuery()
            ->fetch();
        $options = [];
        foreach ($records as $record) {
            $options[$record['name']] = $record['key'];
        }
        return $options;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultsFromAutocompleteInput(string $value): array
    {
        return [
            'name' => $value,
        ];
    }
}
