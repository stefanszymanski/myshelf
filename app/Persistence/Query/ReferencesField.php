<?php

declare(strict_types=1);

namespace App\Persistence\Query;

use App\Persistence\Data\ListField as DataListField;
use App\Persistence\Database;
use App\Persistence\Table;
use SleekDB\QueryBuilder;
use Illuminate\Support\Str;

// TODO rename to ReverseReferenceField
class ReferencesField extends AbstractField
{
    /**
     * @param string $tableName
     * @param string $fieldName
     * @param \Closure(record $record): (string|int|null) $formatter Is called for each local record with the joined
     *                                                               foreign records and returns a printable
     *                                                               representation of this joined data.
     * @param string $label
     */
    public function __construct(
        protected readonly string $tableName,
        protected readonly string $fieldName,
        protected readonly \Closure $formatter,
        protected readonly string $label,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function modifyQuery(QueryBuilder $qb, string $alias, ?string $queryFieldPath, Database $db, Table $table): QueryBuilder
    {
        $foreignTable = $db->getTable($this->tableName);
        $foreignStore = $foreignTable->store;
        $foreignField = $foreignTable->getDataField($this->fieldName);
        $operator = $foreignField instanceof DataListField
            ? 'CONTAINS'
            : '=';
        $joinedFieldName = Str::random(32);
        return $qb
            ->join(
                fn ($record) => $foreignStore->findBy(["data.{$this->fieldName}", $operator, $record['id']]),
                $joinedFieldName
            )
            ->select([$alias => fn ($record) => ($this->formatter)($record[$joinedFieldName])]);
    }
}
