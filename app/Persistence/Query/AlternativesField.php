<?php

declare(strict_types=1);

namespace App\Persistence\Query;

use App\Persistence\Database;
use App\Persistence\Table;
use Illuminate\Support\Arr;
use SleekDB\QueryBuilder;

class AlternativesField extends AbstractField
{
    /**
     * @param array<string> $queryFieldNames
     * @param string $label
     */
    public function __construct(
        protected readonly array $queryFieldNames,
        protected readonly string $label,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function modifyQuery(QueryBuilder $qb, string $alias, ?string $queryFieldPath, Database $db, Table $table): QueryBuilder
    {
        foreach ($this->queryFieldNames as $queryFieldName) {
            if ($queryFieldPath) {
                $queryFieldName .= ":$queryFieldPath";
            }
            $table->modifyQueryForField($qb, $queryFieldName);
        }
        return $qb;
    }

    /**
     * {@inheritDoc}
     */
    public function modifyResult(array $result, string $alias, ?string $queryFieldPath): array
    {
        foreach ($result as &$record) {
            $value = null;
            foreach ($this->queryFieldNames as $queryFieldName) {
                if ($queryFieldPath) {
                    $queryFieldName .= ":$queryFieldPath";
                }
                if (!$value && Arr::get($record, $queryFieldName)) {
                    $value = $record[$queryFieldName];
                    break;
                }
            }
            $record[$alias] = $value;
        }
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(string $queryFieldName, ?string $queryFieldPath, Database $db, Table $table): string
    {
        if ($queryFieldPath) {
            $name = $this->queryFieldNames[0];
            $labelFull = $table->getQueryFieldLabel("$name:$queryFieldPath");
            $labelBase = $table->getQueryFieldLabel($name);
            return $this->label . ' | ' . trim(substr($labelFull, strlen($labelBase)), ' |');
        } else {
            return $this->label;
        }
    }
}
