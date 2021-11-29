<?php

namespace App\Persistence\Schema;

use App\Persistence\Database;
use App\Persistence\Field;
use App\Persistence\Query\Field as QueryField;
use App\Persistence\Query\Filter as QueryFilter;
use App\Persistence\Query\FieldType as QueryFieldType;
use App\Persistence\Reference;
use App\Persistence\ReferenceField;
use App\Utility\RecordUtility;
use App\Validator\NotEmptyValidator;
use SleekDB\Classes\ConditionsHandler;
use SleekDB\QueryBuilder;

abstract class AbstractSchema implements Schema
{
    public const FIELD_TYPE_REAL = 'real';
    public const FIELD_TYPE_VIRTUAL = 'virtual';
    public const FIELD_TYPE_JOINED = 'joined';

    /**
     * Default fields to use in the list view.
     *
     * @var array<string>
     */
    protected array $defaultListFields = ['id', 'key'];

    /**
     * Name of the schema.
     *
     * If empty it will be determined from the class name.
     *
     * @var ?string
     */
    protected ?string $label = null;

    /**
     * List of field names for the creation of record keys.
     *
     * This property must be set or `self::createKeyForRecord()` must be overwritten.
     *
     * @var array<string>
     */
    protected array $keyFields = [];

    /**
     * Registered table fields
     *
     * @var array<string,Field>
     */
    protected array $fields = [];

    /**
     * Registered query fields
     *
     * @see self::registerQueryField()
     *
     * @var array<string,QueryField>
     */
    protected array $queryFields = [];

    /**
     * Registered filters
     *
     * @see self::registerFilter()
     *
     * @var array<string,array<string,QueryFilter>>
     */
    protected array $filters = [];

    /**
     * Registered references
     *
     * @var array<string,Reference>
     */
    protected array $references = [];

    public function __construct(protected string $tableName)
    {
        // Register fields that all types have.
        $this
            ->registerQueryField(
                name: 'id',
                label: 'ID',
                type: QueryFieldType::Real,
                description: 'Auto generated unique numeric ID'
            )
            ->registerQueryField(
                name: 'key',
                label: 'Key',
                type: QueryFieldType::Real,
                description: 'Unique human-readable key'
            );
        $this->configure();
    }

    /**
     * Implement this method to define types and filters.
     */
    abstract protected function configure(): void;

    public function getLabel(): string
    {
        // If no name is set, determine it from the class name.
        if (!$this->label) {
            $parts = array_reverse(explode('\\', static::class));
            $this->label = array_pop($parts);
        }
        return $this->label;
    }

    /**
     * {@inheritDoc}
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * {@inheritDoc}
     */
    public function getQueryFields(): array
    {
        return $this->queryFields;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * {@inheritDoc}
     */
    public function getReferences(): array
    {
        return $this->references;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultListFields(): array
    {
        return $this->defaultListFields ?: ['id', 'key'];
    }

    /**
     * {@inheritDoc}
     */
    public function createKeyForRecord(array $record): string
    {
        if (empty($this->keyFields)) {
            throw new \Exception(sprintf('Either %1$s::$keyFields must be set or %1$s::createKeyForRecord() must be overwritten', static::class));
        }
        $fields = [];
        foreach ($this->keyFields as $field) {
            $fields[] = $record[$field];
        }
        return RecordUtility::createKey(...$fields);
    }

    /**
     * Register a reference.
     *
     * @param string $field
     * @param string $table
     * @param bool $multiple
     */
    protected function registerReference(string $field, string $table, bool $multiple): self
    {
        $this->references[$field] = new Reference(
            table: $this->tableName,
            foreignTable: $table,
            foreignField: $field,
            multiple: $multiple,
        );
        return $this;
    }

    protected function registerField(string $name, string $label, bool $required = false, ?callable $question = null, array|callable $validator = [], ?callable $formatter = null, ?string $description = null)
    {
        if (!is_array($validator)) {
            $validator = [$validator];
        }
        if ($required) {
            $validator[] = fn () => new NotEmptyValidator;
        }
        $this->fields[] = new Field(
            table: $this->tableName,
            name: $name,
            label: $label,
            description: $description,
            question: $question,
            validators: $validator,
            formatter: $formatter,
        );
        return $this;
    }

    protected function registerReferenceField(string $name, string $foreignTable, bool $multiple, string $label, bool $required = false, ?callable $formatter = null, ?string $description = null)
    {
        if ($required) {
            $validators[] = fn () => new NotEmptyValidator;
        }
        $this->fields[] = new ReferenceField(
            table: $this->tableName,
            name: $name,
            foreignTable: $foreignTable,
            multiple: $multiple,
            label: $label,
            description: $description,
            formatter: $formatter,
        );
        return $this;
    }

    public function getFields2(): array
    {
        return $this->fields;
    }


    /**
     * Register a new field.
     *
     * @param string $name Unique identifier of the field
     * @param string $label
     * @param QueryFieldType $type Field type, must be one of the class constants that start with FIELD_TYPE_
     * @param string|null $description
     * @param callable(QueryBuilder,string,Database): QueryBuilder $queryModifier
     * @return self
     */
    protected function registerQueryField(string $name, string $label, QueryFieldType $type, ?string $description = null, callable $queryModifier = null): self
    {
        if (!$queryModifier) {
            $queryModifier = fn (QueryBuilder $qb) => $qb->select([$name]);
        }
        $this->queryFields[$name] = new QueryField(
            name: $name,
            label: $label,
            description: $description,
            type: $type,
            modifyQuery: $queryModifier
        );
        return $this;
    }

    /**
     * Register a filter.
     *
     * @param string $field
     * @param string $operator
     * @param callable(QueryBuilder,mixed,Database):QueryBuilder $queryModifier The second argument is a user provided value to filter for
     * @param string|null $description
     * @return self
     */
    protected function registerFilter(string $field, string $operator, callable $queryModifier, string $description = null): self
    {
        if (!isset($this->filters[$field])) {
            $this->filters[$field] = [];
        }
        $this->filters[$field][$operator] = new QueryFilter(
            field: $field,
            operator: $operator,
            description: $description,
            modifyQuery: $queryModifier,
        );
        return $this;
    }

    /**
     * Register a filter on a field of the same table.
     *
     * The filter is identified by the combination of $name and $operator.
     *
     * @param string $field
     * @param string $operator
     * @param callable(mixed):array<mixed> $queryModifier Callable that receives user input and returns a SleekDB Criteria
     * @param string|null $description
     * @return self
     */
    protected function registerSimpleFilter(string $field, string $operator, callable $queryModifier, string $description = null): self
    {
        $this->registerFilter(
            field: $field,
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
     * @param string $field
     * @param string $operator
     * @param callable(Database):Store $foreignStore Foreign store factory
     * @param callable(array<string,mixed>):array<mixed> $foreignCriteria Is called with a record of the original store
     *                                                    and returns a SleekDB criteria
     * @param string|callable(array<string,mixed>):mixed $foreignField Either the field name of a foreign store or a callable
     *                                                                  that is called with each foreign record and returns some value
     *                                                                  that is compared against the user input
     * @param string $foreignOperator A operator that is supported by ConditionsHandler::verifyCondition()
     * @param string|null $description
     * @return self
     */
    protected function registerJoinedStoreFilter(string $field, string $operator, callable $foreignStore, callable $foreignCriteria, string|callable $foreignField, string $foreignOperator, string $description = null): self
    {
        return $this->registerFilter(
            field: $field,
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
     * @param string $field
     * @param string $operator
     * @param callable(Database): Store $foreignStore Foreign store factory
     * @param callable(array<string,mixed>): array<mixed> $foreignCriteria Is called with a record of the original store
     *                                                                     and returns a SleekDB criteria
     * @param callable(array<array<string,mixed>>): mixed $foreignValue Is called with all foreign records at once
     *                                                                  and returns the value that is compared against the user input
     * @param string $foreignOperator A operator that is supported by ConditionsHandler::verifyCondition()
     * @param string|null $description
     * @return self
     */
    protected function registerJoinedStoreFilter2(string $field, string $operator, callable $foreignStore, callable $foreignCriteria, callable $foreignValue, string $foreignOperator, string $description = null): self
    {
        return $this->registerFilter(
            field: $field,
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
}
