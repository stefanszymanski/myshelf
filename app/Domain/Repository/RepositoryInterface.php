<?php

namespace App\Domain\Repository;

use SleekDB\QueryBuilder;
use SleekDB\Store;

interface RepositoryInterface
{
    public function find(array $fields, array $orderBy = null, array $filters = []): array;

    public function getQueryBuilder(): QueryBuilder;

    public function getStore(): Store;
}

