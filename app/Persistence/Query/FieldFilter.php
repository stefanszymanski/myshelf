<?php

declare(strict_types=1);

namespace App\Persistence\Query;

use App\Persistence\Database;
use App\Persistence\Table;
use SleekDB\Classes\ConditionsHandler;
use SleekDB\QueryBuilder;

class FieldFilter implements Filter
{
    /**
     * @var callable
     */
    protected mixed $queryModifier;

    /**
     * @param string $fieldName Name of the field that is filtered
     * @param array<string,string> $operators Keys are the public operators, values are the sleekDB operators
     */
    public function __construct(
        protected readonly string $fieldName,
        protected readonly array $operators,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function canHandle(string $filterName, string $filterOperator, Database $db): bool
    {
        return $this->fieldName === $filterName && isset($this->operators[$filterOperator]);
    }

    /**
     * {@inheritDoc}
     */
    public function modifyQuery(
        QueryBuilder $qb,
        string $filterName,
        string $filterOperator,
        string|int|bool|array $filterValue,
        Database $db,
        Table $table
    ): QueryBuilder {
        $queryField = $table->getQueryField($this->fieldName);
        // TODO only apply the query field when it wasn't already applied
        $qb = $queryField->modifyQuery($qb, $this->fieldName, null, $db, $table);
        $sleekDbOperator = $this->operators[$filterOperator];
        if (in_array($sleekDbOperator, ['IN', 'NOT IN']) && !is_array($filterValue)) {
            $filterValue = explode(',', $filterValue);
        }
        return $qb->having([fn($record) => ConditionsHandler::verifyCondition($sleekDbOperator, $record[$this->fieldName], $filterValue)]);
    }
}
