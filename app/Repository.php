<?php

namespace App;

use App\Database;
use App\Domain\Type\TypeInterface;
use SleekDB\QueryBuilder;
use SleekDB\Store;

class Repository
{
    public function __construct(protected Store $store, protected TypeInterface $type, protected Database $db)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function find(array $fields, array $orderBy = null, array $filters = [], array $excludeFields = []): array
    {
        // TODO replace method arguments with a Demand object
        // TODO check for invalid values in $fields and $orderBy
        $qb = $this->getQueryBuilder();
        $qb->except($excludeFields);
        foreach ($fields as $fieldName) {
            $this->type->modifyQueryForField($this->db, $qb, $fieldName);
        }
        foreach ($filters as list($fieldName, $operator, $fieldValue)) {
            $qb = $this->type->modifyQueryForFilter($this->db, $qb, $fieldName, $operator, $fieldValue);
        }
        $qb->orderBy($orderBy);
        return $qb->getQuery()->fetch();
    }

    /**
     * {@inheritDoc}
     */
    public function getStore(): Store
    {
        return $this->store;
    }

    /**
     * {@inheritDoc}
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->store->createQueryBuilder();
    }
}
