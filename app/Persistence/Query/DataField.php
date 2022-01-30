<?php

declare(strict_types=1);

namespace App\Persistence\Query;

use App\Persistence\Data\MultivalueFieldContract;
use App\Persistence\Data\ReferenceFieldContract;
use App\Persistence\Database;
use App\Persistence\Table;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SleekDB\QueryBuilder;

class DataField extends AbstractField
{
    /**
     * @param string $dataFieldName
     * @param string $label
     */
    public function __construct(
        protected readonly string $dataFieldName,
        protected readonly string $label,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function modifyQuery(QueryBuilder $qb, string $alias, ?string $queryFieldPath, Database $db, Table $table): QueryBuilder
    {
        $dataFieldName = $this->dataFieldName;
        $dataField = $table->getDataField($dataFieldName);
        if (!$queryFieldPath) {
            $select = fn (array $record) => $dataField->formatValue(Arr::get($record, "data.$dataFieldName"));
            return $qb->select([$alias => $select]);
        } else {
            $isMultivalue = $dataField instanceof MultivalueFieldContract;
            if ($isMultivalue) {
                $dataField = $dataField->getField();
            }
            if (!($dataField instanceof ReferenceFieldContract)) {
                throw new \InvalidArgumentException('Query Sub Fields are only allowed for references');
            }
            $foreignTable = $db->getTable($dataField->getReferredTableName());
            $joinedFieldName = Str::random(32);
            return $qb
                ->join(function ($record) use ($foreignTable, $queryFieldPath, $isMultivalue, $dataFieldName) {
                    $foreignRecordIds = Arr::get($record, "data.$dataFieldName");
                    if (empty($foreignRecordIds)) {
                        return [];
                    }
                    if (!$isMultivalue) {
                        $foreignRecordIds = [$foreignRecordIds];
                    }
                    return $foreignTable->findByIds($foreignRecordIds, fields: [$queryFieldPath]);
                }, $joinedFieldName)
                ->select([$alias => function ($record) use ($joinedFieldName, $isMultivalue, $queryFieldPath) {
                    $foreignRecords = $record[$joinedFieldName];
                    return match (true) {
                        empty($foreignRecords) => null,
                        $isMultivalue => implode("\n", array_unique(array_column($foreignRecords, $queryFieldPath))),
                        default => $foreignRecords[0][$queryFieldPath]
                    };
                }]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(string $queryFieldName, ?string $queryFieldPath, Database $db, Table $table): string
    {
        if (!$queryFieldPath) {
            return $this->label;
        }
        list($subQueryFieldName, $subQueryFieldPath) = array_pad(explode(':', $queryFieldPath, 2), 2, null);
        $foreignTable = $this->getForeignTable($queryFieldName, $db, $table);
        $foreignField = $foreignTable->getQueryField($subQueryFieldName);
        return $this->label . ' | ' . $foreignField->getLabel($subQueryFieldName, $subQueryFieldPath, $db, $foreignTable);
    }

    /**
     * Get the foreign table of a reference data field.
     *
     * @param string $queryFieldName
     * @param Database $db
     * @param Table $table The current table
     * @return Table
     * @throws InvalidArgumentException
     */
    protected function getForeignTable(string $queryFieldName, Database $db, Table $table): Table
    {
        $dataField = $table->getDataField($this->dataFieldName);
        $isMultivalue = $dataField instanceof MultivalueFieldContract;
        if ($isMultivalue) {
            $dataField = $dataField->getField();
        }
        if (!($dataField instanceof ReferenceFieldContract)) {
            throw new \InvalidArgumentException('Query Sub Fields are only allowed for references');
        }
        return $db->getTable($dataField->getReferredTableName());
    }
}
