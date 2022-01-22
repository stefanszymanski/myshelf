<?php

declare(strict_types=1);

namespace App\Persistence\Query;

use App\Persistence\Database;
use App\Persistence\Table;
use SleekDB\QueryBuilder;

class SelectField extends AbstractField
{
    /**
     * @param sleekdb-select $select A SleekDB criteria
     * @param string $label
     */
    public function __construct(
        protected readonly string|array|\Closure $select,
        protected readonly string $label,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function modifyQuery(QueryBuilder $qb, string $alias, ?string $queryFieldPath, Database $db, Table $table): QueryBuilder
    {
        return $qb->select([$alias => $this->select]);
    }
}
