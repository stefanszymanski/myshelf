<?php

namespace App\Domain\Repository;

use SleekDB\QueryBuilder;
use SleekDB\Store;

interface RepositoryInterface
{
    /**
     * Find records.
     *
     * @param array<string> $fields List of field names to include in the records
     * @param array<string,string> $orderBy Keys are field names, values are either 'asc' or 'desc'
     * @param array<array{string,string,mixed}> $filters Tuples with filter name, operator and user given value
     * @param array<string> $excludeFields List of field names to exclude in the records
     * @return array<array> List of found records
     */
    public function find(array $fields, array $orderBy = null, array $filters = [], array $excludeFields = []): array;

    /**
     * Get a QueryBuilder instance.
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder(): QueryBuilder;

    /**
     * Get the store instance.
     *
     * @return Store
     */
    public function getStore(): Store;
}

