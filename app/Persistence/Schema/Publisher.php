<?php

namespace App\Persistence\Schema;

use App\Persistence\Database;
use App\Persistence\FieldType;
use SleekDB\QueryBuilder;
use SleekDB\Store;

class Publisher extends AbstractSchema
{
    protected function configure(): void
    {
        $this
            ->registerField(
                name: 'name',
                label: 'Name',
                type: FieldType::Real,
            )
            ->registerField(
                name: 'books',
                label: 'Books',
                description: 'Number of books',
                type: FieldType::Joined,
                queryModifier: function (QueryBuilder $qb, string $fieldName, Database $db) {
                    return $qb
                        ->join(fn ($publisher) => $db->books()->findBy(['publisher', '=', $publisher['key']]), '_books')
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
     * Parse the user input from the record selection into record default values.
     *
     * @param string $value The user input
     * @return array Record defaults
     */
    public function getDefaultsFromAutocompleteInput(string $value): array
    {
        return [
            'name' => $value,
        ];
    }
}
