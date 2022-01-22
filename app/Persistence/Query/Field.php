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
     * Get a sub field.
     *
     * @param string $queryFieldName
     * @param Database $db
     * @param Table $table
     * @return Field
     */
    public function getSubQueryField(string $queryFieldName, Database $db, Table $table): Field;

    /**
     * Get the field label.
     *
     * @return string
     */
    public function getLabel(): string;
}
