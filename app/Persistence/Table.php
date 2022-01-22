<?php

declare(strict_types=1);

namespace App\Persistence;

use App\Persistence\Data\ContainerFieldContract;
use App\Persistence\Data\Field as DataField;
use App\Persistence\Data\References;
use App\Persistence\Query\Field as QueryField;
use App\Persistence\Query\Filter as QueryFilter;
use App\Persistence\Schema\Schema;
use InvalidArgumentException;
use SleekDB\Exceptions\InvalidArgumentException as ExceptionsInvalidArgumentException;
use SleekDB\QueryBuilder;
use SleekDB\Store;

class Table
{
    /**
     * @param Database $db
     * @param Schema $schema
     * @param Store $store
     * @param string $name
     */
    public function __construct(
        protected Database $db,
        protected Schema $schema,
        public readonly Store $store,
        public readonly string $name,
    ) {
    }


    /* **************
     * Record methods
     * **************/

    /**
     * Get the title for a record.
     *
     * @param int|record $recordOrId
     * @return string
     */
    public function getRecordTitle(int|array $recordOrId): string
    {
        $record = is_int($recordOrId)
            ? $this->store->findById($recordOrId)
            : $recordOrId;
        return $this->schema->getRecordTitle($record, $this, $this->db);
    }

    /* **************************
     * Data Field related methods
     * **************************/

    /**
     * Get all data fields.
     *
     * @return array<string,DataField>
     */
    public function getDataFields(): array
    {
        return $this->schema->getDataFields();
    }

    /**
     * Get a data field by its path.
     *
     * @param string $fieldName
     * @return DataField
     * @throws InvalidArgumentException if `$fieldName` is invalid.
     */
    public function getDataField(string $fieldName): DataField
    {
        $dataFields = $this->getDataFields();
        if (str_contains($fieldName, '.')) {
            list($rootFieldName, $subFieldName) = explode('.', $fieldName, 2);
            if (!isset($dataFields[$rootFieldName])) {
                throw new \InvalidArgumentException("Invalid data field name: $fieldName");
            }
            $rootField = $dataFields[$rootFieldName];
            if (!($rootField instanceof ContainerFieldContract)) {
                throw new \InvalidArgumentException("Invalid data field name: $fieldName");
            }
            $field = $rootField->getField($subFieldName);
        } else {
            if (!isset($dataFields[$fieldName])) {
                throw new \InvalidArgumentException("Invalid data field name: $fieldName");
            }
            $field = $dataFields[$fieldName];
        }
        return $field;
    }


    /* ***************************
     * Query Field related methods
     * ***************************/

    /**
     * Get all query fields.
     *
     * @return array<string,QueryField>
     */
    public function getQueryFields(): array
    {
        return $this->schema->getQueryFields();
    }

    /**
     * Get a query field by its name.
     *
     * @param string $fieldName
     * @return QueryField
     * @throw \InvalidArgumentException if there is no query field with the given name.
     */
    public function getQueryField(string $fieldName): QueryField
    {
        return $this->getQueryFields()[$fieldName] ?? throw new \InvalidArgumentException("Invalid query field name: $fieldName");
    }

    /**
     * Get the names of all query fields.
     *
     * @return array<string>
     */
    public function getQueryFieldNames(): array
    {
        return array_keys($this->getQueryFields());
    }

    /**
     * Get query field labels by field names.
     *
     * @param array<string> $queryFieldNames
     * @return array<string>
     */
    public function getQueryFieldLabels(array $queryFieldNames): array
    {
        return array_map($this->getQueryFieldLabel(...), $queryFieldNames);
    }

    /**
     * Get the label for a query field by its name.
     *
     * @param string $queryFieldName
     * @return string
     */
    public function getQueryFieldLabel(string $queryFieldName): string
    {
        $labelParts = [];
        list($queryFieldName, $subQueryFieldName) = $this->splitQueryFieldPath($queryFieldName);
        $queryField = $this->getQueryField($queryFieldName);
        $labelParts[] = $queryField->getLabel();
        while ($subQueryFieldName) {
            list($queryFieldName, $subQueryFieldName) = $this->splitQueryFieldPath($subQueryFieldName);
            $queryField = $queryField->getSubQueryField($queryFieldName, $this->db, $this);
            $labelParts[] = $queryField->getLabel();
        }
        return implode(' | ', $labelParts);
    }

    /**
     * Split a query field path into its first part and rest.
     *
     * @param string $queryFieldPath
     * @return array{string,string}
     */
    protected function splitQueryFieldPath(string $queryFieldPath): array
    {
        return array_pad(explode(':', $queryFieldPath, 2), 2, null);
    }

    /**
     * Check for invalid query field names.
     *
     * @param array<string> $queryFieldNames
     * @return array<string> List of invalid field names
     */
    public function checkQueryFieldNames(array $queryFieldNames): array
    {
        return collect($queryFieldNames)
            ->map(fn ($name) => explode(':', $name, 2)[0])
            ->filter(fn ($name) => !in_array($name, $this->getQueryFieldNames()))
            ->all();
    }


    /* ***************************
     * Query Field related methods
     * ***************************/

    /**
     * Get all query filters.
     *
     * @return array<QueryFilter>
     */
    public function getQueryFilters(): array
    {
        $filters = [];
        foreach ($this->schema->getQueryFilters() as $fieldName => $fieldFilters) {
            foreach ($fieldFilters as $fieldFilter) {
                $filters[] = $fieldFilter;
            }
        }
        return $filters;
    }

    /**
     * Find a query filter for the given name and operator.
     *
     * @param string $filterName
     * @param string $operator
     * @return QueryFilter
     * @throws InvalidArgumentException if no suitable filter is found.
     */
    public function findQueryFilter(string $filterName, string $operator): QueryFilter
    {
        foreach ($this->schema->getQueryFilters() as $name => $queryFilter) {
            if (str_starts_with($filterName, $name) && $queryFilter->canHandle($filterName, $operator, $this->db)) {
                return $queryFilter;
            }
        }
        throw new \InvalidArgumentException("Cannot find filter: $filterName $operator");
    }


    /* *********************
     * Query related methods
     * *********************/

    /**
     * @param array<string> $fields
     * @param array<string,string> $orderBy
     * @param array<array{string,string,mixed}> $filters
     * @param array<string> $excludeFields
     * @return array<array<string,mixed>>>
     */
    public function find(array $fields, array $orderBy = [], array $filters = [], array $excludeFields = []): array
    {
        $qb = $this->store->createQueryBuilder();
        $qb->except($excludeFields);
        foreach ($fields as $fieldName) {
            $this->modifyQueryForField($qb, $fieldName);
        }
        foreach ($filters as list($fieldName, $operator, $fieldValue)) {
            $qb = $this->modifyQueryForFilter($qb, $fieldName, $operator, $fieldValue);
        }
        $qb->orderBy($orderBy);
        $qb->limit($limit);
        $qb->skip($offset);
        return $qb->getQuery()->fetch();
    }

    /**
     * @param array<int> $ids
     * @param array<string> $fields
     * @return array<record>
     * @throws ExceptionsInvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function findByIds(array $ids, array $fields): array
    {
        return $this->find(fields: $fields, filters: [['id', '#', $ids]]);
    }

    /**
     * @param int $id
     * @param array<string> $fields
     * @return null|record
     * @throws ExceptionsInvalidArgumentException
     */
    public function findById(int $id, array $fields): ?array
    {
        $records = $this->findByIds([$id], $fields);
        return empty($records) ? null : $records[0];
    }

    /**
     * Modify a query to include the given field.
     *
     * @param QueryBuilder $qb
     * @param string $fieldPath
     * @return QueryBuilder
     */
    protected function modifyQueryForField(QueryBuilder $qb, string $fieldPath): QueryBuilder
    {
        list($fieldName, $subFieldPath) = array_pad(explode(':', $fieldPath, 2), 2, null);
        $field = $this->getQueryField($fieldName);
        return $field->modifyQuery($qb, $fieldPath, $subFieldPath, $this->db, $this);
    }

    /**
     * Modify a query to apply the given filter.
     *
     * @param QueryBuilder $qb
     * @param string $fieldName
     * @param string $operator
     * @param mixed $fieldValue
     * @return QueryBuilder
     */
    protected function modifyQueryForFilter(QueryBuilder $qb, string $fieldName, string $operator, mixed $fieldValue): QueryBuilder
    {
        $filter = $this->findQueryFilter($fieldName, $operator);
        return $filter->modifyQuery($qb, $fieldName, $operator, $fieldValue, $this->db, $this);
    }


    /* *************************
     * Reference related methods
     * *************************/

    /**
     * @return References
     */
    public function getReferences(): References
    {
        $references = new References;
        foreach ($this->schema->getDataFields() as $fieldName => $field) {
            $subReferences = $field->getReferences();
            $subReferences->indent($fieldName);
            $references->merge($subReferences);
        }
        return $references;
    }

    /**
     * Find records referring the specified record
     *
     * @param int $id Key of the references record
     * @return array<string,array<string,mixed>> Key is {type}.{field}, value is the referring record
     */
    public function findReferringRecords(int $id): array
    {
        $records = [];
        $references = $this->findReferences($this->name);
        foreach ($references as list($referringTableName, $referringFieldName)) {
            $referringStore = $this->db->getTable($referringTableName)->store;
            // TODO determine the operator and field name without string functions
            // TODO handle list structs, e.g. "content.*.authors.*"
            $operator = str_ends_with($referringFieldName, '.*')
                ? 'CONTAINS'
                : '=';
            $referringFieldName = substr($referringFieldName, 0, -2);
            $referringRecords = $referringStore->findBy([$referringFieldName, $operator, $id]);
            if (!empty($referringRecords)) {
                $records[sprintf('%s.%s', $referringTableName, $referringFieldName)] = $referringRecords;
            }
        }
        return $records;
    }

    /**
     * @param string $targetTableName
     * @return array<array{string,string}> List of tuples of referring table and field name
     */
    private function findReferences(string $targetTableName): array
    {
        $references = [];
        foreach ($this->db->getTables() as $originTable) {
            $originReferences = $originTable->getReferences();
            foreach ($originReferences->get() as $originFieldName => list($referenceTableName, $fieldPath)) {
                if ($referenceTableName === $targetTableName) {
                    $references[] = [$originTable->name, $fieldPath];
                }
            }
        }
        return $references;
    }


    /* **********************
     * Dialog related methods
     * **********************/

    /**
     * Get data fields to ask for in the New Record Dialog.
     *
     * @return array<DataField>
     */
    public function getNewRecordDialogFields(): array
    {
        $fieldNames = $this->schema->getNewRecordDialogFieldNames();
        return collect($this->getDataFields())
            ->filter(fn ($field, $fieldName) => in_array($fieldName, $fieldNames))
            ->all();
    }

    /**
     * Get a list of default query fields for the list view.
     *
     * @return array<string>
     */
    public function getDefaultListQueryFields(): array
    {
        return $this->schema->getDefaultListFields();
    }

    /**
     * Get autocomplete options for a record selection dialog.
     *
     * @return array<string,string> Key is the autocomplete text, value is a record key
     */
    public function getAutocompleteOptions(): array
    {
        return $this->schema->getAutocompleteOptions($this->store);
    }

    /**
     * Parse the user input from record selection dialog into record default values.
     *
     * @param string $value
     * @return record
     */
    public function getDefaultsFromAutocompleteInput(string $value): array
    {
        return $this->schema->getDefaultsFromAutocompleteInput($value);
    }
}
