<?php

namespace App\Domain\Type;

use App\Database;
use SleekDB\QueryBuilder;

class Publisher extends AbstractType
{
    protected function configure(): void
    {
        $this
            ->registerField(
                name: 'name',
                label: 'Name',
                type: self::FIELD_TYPE_REAL,
            )
            ->registerField(
                name: 'books',
                label: 'Books',
                description: 'Number of books',
                type: self::FIELD_TYPE_JOINED,
                queryModifier: function (QueryBuilder $qb, string $fieldName, Database $db) {
                    return $qb
                        ->join(fn ($publisher) => $db->books()->findBy(['publisher', '=', $publisher['key']]), '_books')
                        ->select([$fieldName => ['LENGTH' => '_books']]);
                }
            );

        $this
            ->registerSimpleFilter(
                name: 'name',
                operator: '=',
                description: 'Exact match on name',
                queryModifier: fn ($value) => ['name', '=', $value],
            )
            ->registerSimpleFilter(
                name: 'name',
                operator: '~',
                description: 'Pattern match on name',
                queryModifier: fn ($value) => ['name', 'LIKE', $value],
            );
    }
}
