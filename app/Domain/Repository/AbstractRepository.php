<?php

namespace App\Domain\Repository;

use App\Configuration;
use App\Database;
use App\Domain\Type\TypeInterface;
use SleekDB\QueryBuilder;
use SleekDB\Store;

abstract class AbstractRepository implements RepositoryInterface
{
    protected TypeInterface $type;

    protected Store $store;

    public function __construct(protected Database $db, protected Configuration $configuration)
    {
        // Get the type name from the class name of the repository and set type and store properties.
        $classNameParts = explode('\\', static::class);
        $className = array_pop($classNameParts);
        $typeName = strtolower(substr($className, 0, -10));
        $this->type = $configuration->getType($typeName);
        $this->store = $db->getStore(sprintf('%ss', $typeName));
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
