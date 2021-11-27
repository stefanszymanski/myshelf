<?php

declare(strict_types=1);

namespace App\Persistence;

class Filter
{
    protected $queryModifier;

    public function __construct(
        public readonly string $field,
        public readonly string $operator,
        callable $modifyQuery,
        public readonly ?string $description = null,
    ) {
    }

    public function modifyQuery(QueryBuilder $qb, string $fieldName, Database $db): QueryBuilder
    {
        return call_user_func($this->queryModifier, $qb, $fieldName, $db);
    }
}
