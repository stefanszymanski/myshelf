<?php

namespace App\Persistence\Schema;

use App\Persistence\Database;
use App\Persistence\Field;
use App\Persistence\FieldFactory;
use App\Persistence\Query\Field as QueryField;
use App\Persistence\Query\Filter as QueryFilter;
use App\Persistence\Query\FieldType as QueryFieldType;
use App\Utility\RecordUtility;
use SleekDB\Classes\ConditionsHandler;
use SleekDB\QueryBuilder;
use Symfony\Component\Console\Question\Question;

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
     * @var array<Field>
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

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        // If no name is set, determine it from the class name.
        if (!$this->label) {
            $parts = explode('\\', static::class);
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
    public function getQueryFilters(): array
    {
        return $this->filters;
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
     * Register a table field.
     *
     * @param string $name Field name
     * @param string $label
     * @param bool $required Whether the field must have a non-empty value
     * @param callable(string):Question|null $question A callable that takes a default value as argument and returns a Question object
     * @param array<callable(mixed):mixed>|callable(mixed):mixed $validators One or more callables that take a field value and throw an exception when the validation fails, return the field value
     * @param callable(mixed):string|null $formatter A callable that takes a field value and returns a string representation
     * @param string|null $description
     * @return self
     */
    protected function registerField(string $name, string $label, bool $required = false, ?callable $question = null, array|callable $validators = [], ?callable $formatter = null, ?string $description = null): self
    {
        $fieldFactory = new FieldFactory;
        $this->fields[] = $fieldFactory->createField($this->tableName, $name, $label, $required, $question, $validators, $formatter, $description);
        return $this;
    }

    /**
     * Register a table field that references one or more records of another (or the same) table.
     *
     * @param string $name Field name
     * @param string $foreignTable Name of the referenced table
     * @param bool $multiple Whether the field holds multiple references
     * @param string $label
     * @param callable(mixed):string|null $formatter A callable that takes a field value and returns a string representation
     * @param string|null $description
     * @param bool $required Whether the field must have a non-empty value
     * @param bool $sortable Whether the field values should be sortable, must not be true if `$multiple` is false
     * @return self
     */
    protected function registerReferenceField(string $name, string $foreignTable, string $label, bool $required = false, ?callable $formatter = null, ?string $description = null, bool $multiple = false, bool $sortable = false, ?string $elementLabel = null): self
    {
        $fieldFactory = new FieldFactory;
        $this->fields[] = $fieldFactory->createReferenceField($this->tableName, $name, $foreignTable, $label, $required, $formatter, $description, $multiple, $sortable, $elementLabel);
        return $this;
    }

    /**
     * Register a field for querying records.
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
     * Register a filter for querying.
     *
     * @param string $field
     * @param string $operator
     * @param callable(QueryBuilder,mixed,Database):QueryBuilder $queryModifier The second argument is a user provided value to filter for
     * @param string|null $description
     * @return self
     */
    protected function registerQueryFilter(string $field, string $operator, callable $queryModifier, string $description = null): self
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
    protected function registerSimpleQueryFilter(string $field, string $operator, callable $queryModifier, string $description = null): self
    {
        $this->registerQueryFilter(
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
    protected function registerJoinedStoreQueryFilter(string $field, string $operator, callable $foreignStore, callable $foreignCriteria, string|callable $foreignField, string $foreignOperator, string $description = null): self
    {
        return $this->registerQueryFilter(
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
    protected function registerJoinedStoreQueryFilter2(string $field, string $operator, callable $foreignStore, callable $foreignCriteria, callable $foreignValue, string $foreignOperator, string $description = null): self
    {
        return $this->registerQueryFilter(
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
