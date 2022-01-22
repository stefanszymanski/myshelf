<?php

declare(strict_types=1);

namespace App\Persistence\Query;

use App\Persistence\Data\MultivalueFieldContract;
use App\Persistence\Data\ReferenceFieldContract;
use App\Persistence\Database;
use App\Persistence\Table;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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
    public function getSubQueryField(string $queryFieldName, Database $db, Table $table): Field
    {
        $dataField = $table->getDataField($this->dataFieldName);
        $isMultivalue = $dataField instanceof MultivalueFieldContract;
        if ($isMultivalue) {
            $dataField = $dataField->getField();
        }
        if (!($dataField instanceof ReferenceFieldContract)) {
            throw new \InvalidArgumentException('Query Sub Fields are only allowed for references');
        }
        $foreignTable = $db->getTable($dataField->getReferredTableName());
        return $foreignTable->getQueryField($queryFieldName);
    }
}
