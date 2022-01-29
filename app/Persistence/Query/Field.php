<?php

declare(strict_types=1);

namespace App\Persistence\Query;

use App\Persistence\Database;
use App\Persistence\Table;
use SleekDB\QueryBuilder;

interface Field
{
    /**
     * Modify a query.
     *
     * @param QueryBuilder $qb Query
     * @param string $alias Name of the result field
     * @param string|null $queryFieldPath Path of referrenced query fields
     * @param Database $db
     * @param Table $table Current table
     * @return QueryBuilder The modified query
     */
    public function modifyQuery(QueryBuilder $qb, string $alias, ?string $queryFieldPath, Database $db, Table $table): QueryBuilder;

    /**
     * Modify the query result.
     *
     * @param array<record> $result
     * @param string $alias
     * @param string|null $queryFieldPath
     * @return array<record>
     */
    public function modifyResult(array $result, string $alias, ?string $queryFieldPath): array;

    /**
     * Get the field label.
     *
     * @return string
     */
    public function getLabel(string $alias, ?string $queryFieldPath, Database $db, Table $table): string;
}
