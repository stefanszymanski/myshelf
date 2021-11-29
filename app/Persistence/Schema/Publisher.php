<?php

namespace App\Persistence\Schema;

use App\Persistence\Database;
use App\Persistence\Query\FieldType as QueryFieldType;
use SleekDB\QueryBuilder;
use SleekDB\Store;

class Publisher extends AbstractSchema
{
    protected array $keyFields = ['name'];

    protected function configure(): void
    {
        $this
            ->registerField(
                name: 'name',
                label: 'Full name',
                required: true
            )
            ->registerField(
                name: 'shortname',
                label: 'Short name',
            );

        $this
            ->registerQueryField(
                name: 'name',
                label: 'Name',
                type: QueryFieldType::Real,
            )
            ->registerQueryField(
                name: 'books',
                label: 'Books',
                description: 'Number of books',
                type: QueryFieldType::Joined,
                queryModifier: function (QueryBuilder $qb, string $fieldName, Database $db) {
                    $bookStore = $db->books()->store;
                    return $qb
                        ->join(fn ($publisher) => $bookStore->findBy(['publisher', '=', $publisher['key']]), '_books')
                        ->select([$fieldName => ['LENGTH' => '_books']]);
                }
            );

        $this
            ->registerSimpleFilter(
                field: 'name',
                operator: '=',
                description: 'Exact match on name',
                queryModifier: fn ($value) => ['name', '=', $value],
            )
            ->registerSimpleFilter(
                field: 'name',
                operator: '~',
                description: 'Pattern match on name',
                queryModifier: fn ($value) => ['name', 'LIKE', $value],
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
