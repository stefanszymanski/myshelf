<?php

namespace App\Domain\Repository;

use App\Database;
use App\Domain\Type\TypeInterface;
use SleekDB\QueryBuilder;
use SleekDB\Store;

abstract class AbstractRepository
{
    abstract public function getStore(): Store;

    // TODO find a better way to inject a specific Type as overwriting the constructor
    protected function getType(): TypeInterface
    {
        return $this->type;
    }

    public function __construct(protected Database $db)
    {
    }

    public function find(array $fields, array $orderBy = null): array
    {
        // TODO check for invalid values in $fields and $orderBy

        $qb = $this->getQueryBuilder();

        $select = [];
        foreach ($fields as $field) {
            $fieldConfig = $this->type->getFieldConfiguration($field);
            if (isset($fieldConfig['select'])) {
                $select = array_merge($select, $fieldConfig['select']);
            }
            if (isset($fieldConfig['join']) && isset($fieldConfig['joinAs'])) {
                $qb->join($fieldConfig['join']($this->db), $fieldConfig['joinAs']);
            }
        }

        $qb->select($select);
        $qb->orderBy($orderBy);

        return $qb->getQuery()->fetch();
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->getStore()->createQueryBuilder();
    }

    protected function getField(string $name): array
    {
        return $this->fields[$name];
    }

    protected function registerField(string $name, string $label, array $select = null): self
    {
        $this->fields[$name] = [
            'label' => $label,
            'select' => [$name]
        ];
        return $this;
    }

    protected function registerVirtualField(string $name, string $label, array $select = null): self
    {
        $this->fields[$name] = [
            'label' => $label,
            'select' => [$name => $select]
        ];
        return $this;
    }

    protected function registerJoinField(string $name, string $label, array $select, string $joinAs, callable $join)
    {
        $this->fields[$name] = [
            'label' => $label,
            'select' => [$name => $select],
            'join' => $join,
            'joinAs' => $joinAs,
        ];
        return $this;
    }
}
