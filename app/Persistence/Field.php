<?php

declare(strict_types=1);

namespace App\Persistence;

use App\Persistence\FieldType;
use SleekDB\QueryBuilder;

class Field
{
    protected $queryModifier;

    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly FieldType $type,
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
