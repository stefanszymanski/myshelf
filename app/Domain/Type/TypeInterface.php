<?php

namespace App\Domain\Type;

use App\Database;
use SleekDB\QueryBuilder;

interface TypeInterface
{
    public function getFieldNames(): array;

    public function checkFieldNames(array $fields): array;

    public function getFieldLabels(array $fields): array;

    public function getFieldInfo(): array;

    public function modifyQueryForFilter(Database $db, QueryBuilder $qb, string $fieldName, string $operator, $fieldValue): QueryBuilder;

    public function modifyQueryForField(Database $db, QueryBuilder $qb, string $fieldName): QueryBuilder;
}
