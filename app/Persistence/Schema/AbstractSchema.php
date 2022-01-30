<?php

namespace App\Persistence\Schema;

use App\Persistence\Data\Field as DataField;
use App\Persistence\Database;
use App\Persistence\Query\FieldFactory as QueryFieldFactory;
use App\Persistence\Query\FilterFactory as QueryFilterFactory;
use App\Persistence\Query\Field as QueryField;
use App\Persistence\Query\Filter as QueryFilter;
use App\Persistence\Table;
use InvalidArgumentException;

abstract class AbstractSchema implements Schema
{
    /**
     * Data fields to create a records title from.
     *
     * The first non-empty field in list is used.
     *
     * @var array<string>
     */
    protected array $recordTitleFields = [];

    /**
     * Data fields to ask for in the New Record Dialog.
     *
     * @var array<string>
     */
    protected array $newRecordDialogFields = ['name', 'shortname'];

    /**
     * Default fields to use in the list view.
     *
     * @var array<string>
     */
    protected array $defaultListFields = ['id'];

    /**
     * Registered data fields
     *
     * @var array<string,DataField>
     */
    protected array $dataFields = [];

    /**
     * Registered query fields
     *
     * @var array<string,QueryField>
     */
    protected array $queryFields = [];

    /**
     * Registered filters
     *
     * @var array<string,QueryFilter>
     */
    protected array $queryFilters = [];

    /**
     * Label of the schema.
     *
     * @var string
     */
    protected ?string $label = null;

    /**
     * @param string $tableName
     * @return void
     */
    public function __construct(protected string $tableName)
    {
        $this->registerQueryField('id', QueryFieldFactory::raw('id', label: 'ID'));
        $this->registerQueryFilters([
            'id' => QueryFilterFactory::forfield('id', equal: true, unequal: true,gt: true, lt: true, gte: true, lte: true, in: true)
        ]);
        $this->configure();
    }

    /**
     * Implement this method to define fields and filters.
     */
    abstract protected function configure(): void;

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return $this->label ?? ucfirst(collect(explode('\\', static::class))->last());
    }

    /**
     * {@inheritDoc}
     */
    public function getDataFields(): array
    {
        return $this->dataFields;
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
        return $this->queryFilters;
    }

    /**
     * {@inheritDoc}
     */
    public function getQueryFilter(string $fieldName, string $operator): QueryFilter
    {
        if (!isset($this->queryFilters[$fieldName]) || !isset($this->queryFilters[$fieldName][$operator])) {
            throw new \InvalidArgumentException("Invalid query filter name and operator: $fieldName, $operator");
        }
        return $this->queryFilters[$fieldName][$operator];
    }

    /**
     * {@inheritDoc}
     */
    public function getRecordTitle(array $record, Table $table, Database $db): string
    {
        if (empty($this->recordTitleFields)) {
            return $record['id'] ?? '';
        }
        $fieldName = collect($this->recordTitleFields)
            ->first(fn($fieldName) => isset($record['data'][$fieldName]) && !empty($record['data'][$fieldName]));
        return $fieldName
            ? $this->dataFields[$fieldName]->formatValue($record['data'][$fieldName])
            : sprintf('<error>%s #%s</>', $this->getLabel(), $record['id'] ?? 'new');
    }

    /**
     * {@inheritDoc}
     */
    public function getNewRecordDialogFieldNames(): array
    {
        return $this->newRecordDialogFields;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultListFields(): array
    {
        return $this->defaultListFields ?: ['id'];
    }

    /**
     * Register multiple data fields.
     *
     * @param array<string,DataField> $dataFields
     * @return void
     */
    protected function registerDataFields(array $dataFields): void
    {
        foreach ($dataFields as $name => $dataField) {
            $this->registerDataField($name, $dataField);
        }
    }

    /**
     * Register a data field.

     * @param string $name
     * @param DataField $dataField
     * @return void
     */
    protected function registerDataField(string $name, DataField $dataField): void
    {
        $this->dataFields[$name] = $dataField;
    }

    /**
     * Register multiple query fields.
     *
     * @param array<string,QueryField> $queryFields
     * @return void
     */
    protected function registerQueryFields(array $queryFields): void
    {
        foreach ($queryFields as $name => $queryField) {
            $this->registerQueryField($name, $queryField);
        }
    }

    /**
     * Register a query field.

     * @param string $name
     * @param QueryField $queryField
     * @return void
     */
    protected function registerQueryField(string $name, QueryField $queryField): void
    {
        $this->queryFields[$name] = $queryField;
    }

    /**
     * Reigster multiple query filters.
     *
     * @param array<string,QueryFilter> $queryFilters
     * @return void
     */
    protected function registerQueryFilters(array $queryFilters): void
    {
        foreach ($queryFilters as $name => $queryFilter) {
            $this->registerQueryFilter($name, $queryFilter);
        }
    }

    /**
     * Register a query filter.
     *
     * @param string $name
     * @param QueryFilter $queryFilter
     * @return void
     */
    protected function registerQueryFilter(string $name, QueryFilter $queryFilter): void
    {
        $this->queryFilters[$name] = $queryFilter;
    }
}
