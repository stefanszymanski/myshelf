<?php

declare(strict_types=1);

namespace App\Persistence\Query;

use App\Persistence\Database;
use App\Persistence\Table;
use SleekDB\QueryBuilder;

class RawField extends AbstractField
{
    /**
     * @param string $fieldName
     * @param string $label
     */
    public function __construct(
        protected readonly string $fieldName,
        protected readonly string $label,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function modifyQuery(QueryBuilder $qb, string $alias, ?string $queryFieldPath, Database $db, Table $table): QueryBuilder
    {
        return $qb->select([$this->fieldName]);
    }
}
