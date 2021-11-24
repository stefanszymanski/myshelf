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

    /**
     * Registered fields and their configurations.
     *
     * @see self::registerField()
     *
     * @var array<string, array{name:string,label:string,description:string,type:string,queryModifier:callable(QueryBuilder):QueryBuilder}
     */
    protected array $fields = [];

    /**
     * Registered filters and their configurations.
     *
     * @see self::registerFilter()
     *
     * @var array<string,array<string,array{description:string,queryModifier:callable(QueryBuilder,mixed,Database):QueryModifier}>>
     */
    protected array $filters = [];

    public function __construct()
    {
        // Register fields that all types have.
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

    /**
     * Implement this method to define types and filters.
     */
    abstract protected function configure(): void;

    /**
     * {@inheritDoc}
     */
    public function getFieldNames(): array
    {
        return array_keys($this->fields);
    }

    /**
     * {@inheritDoc}
     */
    public function checkFieldNames(array $fields): array
    {
        return array_filter($fields, fn ($field) => !in_array($field, array_keys($this->fields)));
    }

    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritDoc}
     */
    public function getFieldInfo(): array
    {
        return array_map(function ($field) {
            return [
                'name' => $field['name'],
                'label' => $field['label'],
                'description' => $field['description'],
                'type' => $field['type'],
            ];
        }, $this->fields);
    }

    /**
     * {@inheritDoc}
     */
    public function modifyQueryForFilter(Database $db, QueryBuilder $qb, string $fieldName, string $operator, $fieldValue): QueryBuilder
    {
        $config = $this->filters[$fieldName][$operator];
        return $config['modifyQuery']($qb, $fieldValue, $db);
    }

    /**
     * {@inheritDoc}
     */
    public function modifyQueryForField(Database $db, QueryBuilder $qb, string $fieldName): QueryBuilder
    {
        $config = $this->fields[$fieldName];
        return $config['modifyQuery']($qb, $fieldName, $db);
    }

    /**
     * Register a new field.
     *
     * @param string $name Unique identifier of the field
     * @param string $label
     * @param string $type Field type, must be one of the class constants that start with FIELD_TYPE_
     * @param string|null $description
     * @param callable(QueryBuilder): QueryBuilder $queryModifier
     * @return self
     */
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

    /**
     * Register a filter on a field of the same table.
     *
     * The filter is identified by the combination of $name and $operator.
     *
     * @param string $name
     * @param string $operator
     * @param callable(QueryBuilder, mixed, Database): QueryBuilder $queryModifier Second argument is a user provided value to filter for
     * @param string|null $description
     * @return self
     */
    protected function registerSimpleFilter(string $name, string $operator, callable $queryModifier, string $description = null): self
    {
        $this->registerFilter(
            name: $name,
            operator: $operator,
            description: $description,
            queryModifier: function (QueryBuilder $qb, $value) use ($queryModifier) {
                return $qb->where([$queryModifier($value)]);
            }
        );
        return $this;
    }

    /**
     * Register a filter on a joined store.
     *
     * The filter is identified by the combination of `$name` and `$operator`.
     *
     * In contrast to self::registerJoinedStoreFilter2() `$foreignField` is used to get a value from each record
     * that is compared against. The filter returns `true` if one of the foreign record matches the user input.
     *
     * The method creates a query modifier function that:
     *  1. calls `$foreignStore` to create a Store object
     *  2. calls `$foreignCriteria` with a record of the primary store to create a criteria array
     *  3. calls the created foreign Store object with the created criteria to get foreign records
     *  4. checks for each foreign record if the value of `$foreignField` with `$foreignOperator` matches the user input
     *     until a foreign records matches.
     *
     * @param string $name
     * @param string $operator
     * @param callable(Database): Store $foreignStore Foreign store factory
     * @param callable(array): array<array> $foreignCriteria Is called with a record of the original store
     *                                                       and returns a list of records of the joined store
     * @param string|callable(array): mixed $foreignField Either the field name of a foreign store or a callable
     *                                                    that is called with each foreign record and returns some value
     *                                                    that is compared against the user input
     * @param string $foreignOperator A operator that is supported by ConditionsHandler::verifyCondition()
     * @param string|null $description
     * @return self
     */
    protected function registerJoinedStoreFilter(string $name, string $operator, callable $foreignStore, callable $foreignCriteria, string|callable $foreignField, string $foreignOperator, string $description = null): self
    {
        return $this->registerFilter(
            name: $name,
            operator: $operator,
            description: $description,
            queryModifier: function (QueryBuilder $qb, $filterValue, Database $db) use ($foreignStore, $foreignCriteria, $foreignField, $foreignOperator) {
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
    }

    /**
     * Register a filter on a joined store.
     *
     * The filter is identified by the combination of `$name` and `$operator`.
     *
     * In contrast to self::registerJoinedStoreFilter() `$foreignValue` calculates a single value
     * that is compared against. The filter returns `true` if this value matches the user input.
     *
     * TODO find a better method name
     *
     * The method creates a query modifier function that:
     *  1. calls `$foreignStore` to create a Store object
     *  2. calls `$foreignCriteria` with a record of the primary store to create a criteria array
     *  3. calls the created foreign Store object with the created criteria to get foreign records
     *  4. calls `$foreignValue` to get the value that is compared against the user input
     *  5. returns whether the value returned by `$foreignValue` and `$foreignOperator` matches the user input
     *
     * @param string $name
     * @param string $operator
     * @param callable(Database): Store $foreignStore Foreign store factory
     * @param callable(array): array<array> $foreignCriteria Is called with a record of the original store
     *                                                       and returns a list of records of the joined store
     * @param callable(array<array>): mixed $foreignValue Is called with all foreign records at once
     *                                                    and returns the value that is compared against the user input
     * @param string $foreignOperator A operator that is supported by ConditionsHandler::verifyCondition()
     * @param string|null $description
     * @return self
     */
    protected function registerJoinedStoreFilter2(string $name, string $operator, callable $foreignStore, callable $foreignCriteria, callable $foreignValue, string $foreignOperator, string $description = null): self
    {
        return $this->registerFilter(
            name: $name,
            operator: $operator,
            description: $description,
            queryModifier: function (QueryBuilder $qb, $filterValue, Database $db) use ($foreignStore, $foreignCriteria, $foreignValue, $foreignOperator) {
                return $qb->where([function ($record) use ($db, $foreignStore, $foreignCriteria, $filterValue, $foreignValue, $foreignOperator) {
                    $foreignRecords = $foreignStore($db)->findBy($foreignCriteria($record));
                    $foreignFieldValue = $foreignValue($foreignRecords);
                    return ConditionsHandler::verifyCondition($foreignOperator, $foreignFieldValue, $filterValue);
                }]);
            }
        );
    }

    /**
     * Register a filter.
     *
     * @param string $name
     * @param string $operator
     * @param callable(QueryModifier, mixed, Database): QueryModifier The second argument is a user provided value to filter for
     * @param string|null $description
     * @return self
     */
    protected function registerFilter(string $name, string $operator, callable $queryModifier, string $description = null): self
    {
        if (!isset($this->filters[$name])) {
            $this->filters[$name] = [];
        }
        $this->filters[$name][$operator] = [
            'modifyQuery' => $queryModifier,
            'description' => $description,
        ];
        return $this;
    }

    /**
     * {@inheritDoc}
     */
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
