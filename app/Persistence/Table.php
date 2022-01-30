<?php

declare(strict_types=1);

namespace App\Persistence;

use App\Persistence\Data\ContainerFieldContract;
use App\Persistence\Data\Field as DataField;
use App\Persistence\Data\MultivalueFieldContract;
use App\Persistence\Data\ReferenceFieldContract;
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
     * @var array<string,array{string,bool}>|null
     */
    private ?array $references = null;

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


    /**
     * Get the table label.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->schema->getLabel();
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
     * @param string $queryFieldPath
     * @return string
     */
    public function getQueryFieldLabel(string $queryFieldPath): string
    {
        list($queryFieldName, $subQueryFieldPath) = $this->splitQueryFieldPath($queryFieldPath);
        $queryField = $this->getQueryField($queryFieldName);
        return $queryField->getLabel($queryFieldName, $subQueryFieldPath, $this->db, $this);
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
     * @param int $limit
     * @param int $offset
     * @return array<record>
     */
    public function find(array $fields, array $orderBy = [], array $filters = [], array $excludeFields = [], int $limit = 100, int $offset = 0): array
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
        $result = $qb->getQuery()->fetch();
        foreach ($fields as $fieldName) {
            $result = $this->modifyResultForField($result, $fieldName);
        }
        $fieldNames = array_flip($fields);
        foreach ($result as &$record) {
            $record = array_intersect_key($record, $fieldNames);
            uksort($record, fn ($a, $b) => $fieldNames[$a] <=> $fieldNames[$b]);
        }
        return $result;
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
     * @param string $queryFieldPath
     * @return QueryBuilder
     */
    public function modifyQueryForField(QueryBuilder $qb, string $queryFieldPath): QueryBuilder
    {
        list($fieldName, $subFieldPath) = $this->splitQueryFieldPath($queryFieldPath);
        $field = $this->getQueryField($fieldName);
        return $field->modifyQuery($qb, $queryFieldPath, $subFieldPath, $this->db, $this);
    }

    /**
     * Modify the query result for a query field.
     *
     * @param array<record> $result
     * @param string $queryFieldPath
     * @return array<record>
     */
    protected function modifyResultForField(array $result, string $queryFieldPath): array
    {
        list($fieldName, $subFieldPath) = $this->splitQueryFieldPath($queryFieldPath);
        $field = $this->getQueryField($fieldName);
        return $field->modifyResult($result, $queryFieldPath, $subFieldPath);
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
     * Get reference information about the table.
     *
     * @return array<string,array{string,bool}> Keys are data field paths, values are tuples of the target table name
     */
    public function getReferences(): array
    {
        if ($this->references === null) {
            $this->references = $this->createReferencesMap($this->getDataFields());
        }
        return $this->references;
    }

    /**
     * Find records that reference the given ID.
     *
     * The return value is a list of table names with a list of field names with a list of record IDs.
     *
     * Example output:
     *   [
     *     'book' => [
     *       'persons.author' => [2,6,7,19],
     *       'persons.editor' => [37],
     *     ],
     *     'work' => [
     *       'persons.authors' => [12,13,14,15]
     *     ]
     *   ]
     *
     * @param int $referredRecordId
     * @return array<string,array<string,array<int>>> Pattern: array<tableName,array<fieldPath,recordId[]>>
     */
    public function findReferringRecords(int $referredRecordId): array
    {
        return collect($this->db->getTables())
            ->map(function (Table $table) use ($referredRecordId) {
                return collect($table->getReferences())
                    ->map(function (array $reference, string $fieldName) use ($referredRecordId, $table) {
                        list($referredTableName, $isMultivalue) = $reference;
                        if ($referredTableName != $this->name) {
                            return null;
                        }
                        $operator = $isMultivalue ? 'CONTAINS' : '=';
                        $referringRecords = $table->store->findBy(["data.$fieldName", $operator, $referredRecordId]);
                        return array_column($referringRecords, 'id');
                    })
                    ->filter()
                    ->all();
            })
            ->filter()
            ->all();
    }

    /**
     * Create reference information for the given data fields.
     *
     * @param array<string,DataField> $dataFields
     * @return array<string,array{string,bool}> Keys are data field paths, values are tuples of the target table name
     *                                          and whether it's a multivalue field
     */
    private static function createReferencesMap(array $dataFields): array
    {
        return collect($dataFields)
            ->reduceWithKeys(function ($result, $field, $fieldName) {
                if ($field instanceof ReferenceFieldContract) {
                    $result[$fieldName] = [$field->getReferredTableName(), false];
                }
                if ($field instanceof MultivalueFieldContract && $field->getField() instanceof ReferenceFieldContract) {
                    $result[$fieldName] = [$field->getField()->getReferredTableName(), true];
                }
                if ($field instanceof ContainerFieldContract) {
                    $subResult = collect(self::createReferencesMap($field->getSubFields()))
                        ->mapWithKeys(fn ($subReference, $subFieldName) => ["$fieldName.$subFieldName" => $subReference])
                        ->all();
                    $result = array_merge($result, $subResult);
                }
                return $result;
            }, []);
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
