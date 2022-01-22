<?php

declare(strict_types=1);

namespace App\Persistence\Query;

use App\Persistence\Database;
use App\Persistence\Table;
use SleekDB\QueryBuilder;

interface Filter
{
    /**
     * Check whether the Filter supports the given filter name and operator.
     *
     * @param string $filterName
     * @param string $filterOperator
     * @param Database $db
     * @return bool
     */
    public function canHandle(string $filterName, string $filterOperator, Database $db): bool;

    /**
     * Modify a query.
     *
     * @param QueryBuilder $qb Query to be modified
     * @param string $filterName Name of the filter
     * @param string $filterOperator Operator that is used on the filter
     * @param string $filterValue Value that is filtered for
     * @param Database $db
     * @param Table $table Current table
     * @return QueryBuilder The modified query
     */
    public function modifyQuery(QueryBuilder $qb, string $filterName, string $filterOperator, string $filterValue, Database $db, Table $table): QueryBuilder;
}
