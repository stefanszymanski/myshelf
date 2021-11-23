<?php

namespace App\Domain\Type;

use App\Database;
use SleekDB\Classes\ConditionsHandler;
use SleekDB\QueryBuilder;

abstract class AbstractType implements TypeInterface
{
    public const FIELD_TYPE_REAL = 'real';
    public const FIELD_TYPE_VIRTUAL = 'virtual';
    public const FIELD_TYPE_JOINED = 'joined';

    protected array $fields = [];

    protected array $filters = [];

    public function __construct()
    {
        $this
            ->registerField(
                name: 'id',
                label: 'ID',
                type: self::FIELD_TYPE_REAL,
                description: 'Auto generated unique numeric ID'
            )
            ->registerField(
                name: 'key',
                label: 'Key',
                type: self::FIELD_TYPE_REAL,
                description: 'Unique human-readable key'
            );
        $this->configure();
    }

    abstract protected function configure(): void;

    public function getFieldNames(): array
    {
        return array_keys($this->fields);
    }

    public function checkFieldNames(array $fields): array
    {
        return array_filter($fields, fn ($field) => !in_array($field, array_keys($this->fields)));
    }

    public function getFieldLabels(array $fields = null): array
    {
        if (!$fields) {
            $labels = array_combine(
                array_keys($this->fields),
                array_column($this->fields, 'label')
            );
        } else {
            $labels = [];
            foreach ($fields as $field) {
                $labels[$field] = $this->fields[$field]['label'];
            }
        }
        return $labels;
    }

    public function getFieldInfo(): array
    {
        return array_map((function ($field) {
            return [
                'name' => $field['name'],
                'label' => $field['label'],
                'description' => $field['description'],
                'type' => $field['type'],
            ];
        })->bindTo($this), $this->fields);
    }

    public function modifyQueryForFilter(Database $db, QueryBuilder $qb, string $fieldName, string $operator, $fieldValue): QueryBuilder
    {
        $config = $this->filters[$fieldName][$operator];
        return $config['modifyQuery']($qb, $fieldValue, $db);
    }

    public function modifyQueryForField(Database $db, QueryBuilder $qb, string $fieldName): QueryBuilder
    {
        $config = $this->fields[$fieldName];
        return $config['modifyQuery']($qb, $fieldName, $db);
    }

    protected function registerField(string $name, string $label, string $type, ?string $description = null, callable $queryModifier = null): self
    {
        if (!$queryModifier) {
            $queryModifier = fn (QueryBuilder $qb) => $qb->select([$name]);
        }
        $this->fields[$name] = [
            'name' => $name,
            'label' => $label,
            'type' => $type,
            'description' => $description,
            'modifyQuery' => $queryModifier
        ];
        return $this;
    }

    protected function registerSimpleFilter(string $name, string $operator, callable $filter, string $description = null): self
    {
        $this->registerFilter(
            name: $name,
            operator: $operator,
            description: $description,
            filter: function (QueryBuilder $qb, $value) use ($filter) {
            return $qb->where([$filter($value)]);
        });
        return $this;
    }

    protected function registerJoinedStoreFilter(string $name, string $operator, callable $foreignStore, callable $foreignCriteria, string|callable $foreignField, string $foreignOperator, string $description = null): self
    {
        $this->registerFilter(
            name: $name,
            operator: $operator,
            description: $description,
            filter: function (QueryBuilder $qb, $filterValue, Database $db) use ($foreignStore, $foreignCriteria, $foreignField, $foreignOperator) {
                return $qb->where([function ($record) use ($db, $foreignStore, $foreignCriteria, $filterValue, $foreignField, $foreignOperator) {
                    $foreignRecords = $foreignStore($db)->findBy($foreignCriteria($record));
                    foreach ($foreignRecords as $foreignRecord) {
                        if (is_string($foreignField)) {
                            $foreignFieldValue = $foreignRecord[$foreignField];
                        } else {
                            $foreignFieldValue = $foreignField($foreignRecord);
                        }
                        if (ConditionsHandler::verifyCondition($foreignOperator, $foreignFieldValue, $filterValue)) {
                            return true;
                        };
                    }
                    return false;
                }]);
            }
        );
        return $this;
    }

    protected function registerFilter(string $name, string $operator, callable $filter, string $description = null): self
    {
        if (!isset($this->filters[$name])) {
            $this->filters[$name] = [];
        }
        $this->filters[$name][$operator] = [
            'modifyQuery' => $filter,
            'description' => $description,
        ];
        return $this;
    }

    public function getFilterInfo(): array
    {
        $info = [];
        foreach ($this->filters as $name => $operators) {
            foreach ($operators as $operator => $filterConfiguration) {
                $info[] = [
                    'name' => $name,
                    'operator' => $operator,
                    'description' => $filterConfiguration['description'],
                ];
            }
        }
        return $info;
    }
}
