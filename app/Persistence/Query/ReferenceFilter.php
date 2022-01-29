<?php

declare(strict_types=1);

namespace App\Persistence\Query;

use App\Persistence\Database;
use App\Persistence\Table;
use Illuminate\Support\Arr;
use SleekDB\QueryBuilder;

class ReferenceFilter implements Filter
{
    /**
     * @param string $fieldName
     * @param string $tableName
     * @param bool $isMultivalue
     */
    public function __construct(
        protected readonly string $fieldName,
        protected readonly string $tableName,
        protected readonly bool $isMultivalue,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function canHandle(string $filterName, string $filterOperator, Database $db): bool
    {
        // TODO refactor
        try {
            $foreignFilterName = explode(':', $filterName, 2)[1] ?? null;
            return (!$foreignFilterName && in_array($filterOperator, ['=', '#']))
                ||
                $db->getTable($this->tableName)->findQueryFilter($foreignFilterName, $filterOperator);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function modifyQuery(
        QueryBuilder $qb,
        string $filterName,
        string $filterOperator,
        string $filterValue,
        Database $db,
        Table $table
    ): QueryBuilder {
        // Keep only the part after the first dot.
        $foreignFilterName = explode(':', $filterName, 2)[1] ?? null;
        // Determine IDs of foreign records.
        if ($foreignFilterName) {
            // If there is a foreign filter name, find for foreign records.
            $foreignTable = $db->getTable($this->tableName);
            $foreignRecords = $foreignTable->find(['id'], filters: [[$foreignFilterName, $filterOperator, $filterValue]]);
            $foreignIds = array_column($foreignRecords, 'id');
        } else {
            // If the filter name consist just of the local field, use the filter value as foreign record IDs.
            $foreignIds = $filterOperator === '='
                ? [(int) $filterValue]
                : array_map(intval(...), explode(',', $filterValue));
        }
        // Filter the records: they must contain a reference to the previously found foreign records.
        if ($this->isMultivalue) {
            $qb->where([
                fn ($record) => !empty(array_intersect(
                    Arr::get($record['data'], $this->fieldName, []),
                    $foreignIds
                ))
            ]);
        } else {
            $qb->where(['data.' . $this->fieldName, 'IN', $foreignIds]);
        }
        return $qb;
    }
}
