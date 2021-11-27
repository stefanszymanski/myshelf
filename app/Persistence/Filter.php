<?php

declare(strict_types=1);

namespace App\Persistence;

use SleekDB\QueryBuilder;

class Filter
{
    /**
     * @var callable
     */
    protected mixed $queryModifier;

    public function __construct(
        public readonly string $field,
        public readonly string $operator,
        callable $modifyQuery,
        public readonly ?string $description = null,
    ) {
        $this->queryModifier = $modifyQuery;
    }

    public function modifyQuery(QueryBuilder $qb, string $fieldName, Database $db): QueryBuilder
    {
        return call_user_func($this->queryModifier, $qb, $fieldName, $db);
    }
}
