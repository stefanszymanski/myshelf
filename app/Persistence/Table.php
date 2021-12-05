<?php

declare(strict_types=1);

namespace App\Persistence;

use App\Persistence\Query\Field as QueryField;
use App\Persistence\Query\Filter as QueryFilter;
use App\Persistence\Schema\Schema;
use App\Validator\NewKeyValidator;
use SleekDB\QueryBuilder;
use SleekDB\Store;

class Table
{
    public function __construct(
        protected Database $db,
        protected Schema $schema,
        public readonly Store $store,
        public readonly string $name,
    ) {
    }

    public function getLabel(): string
    {
        return $this->schema->getLabel();
    }

    /**
     * Create a default key for a record.
     *
     * @param array<string,mixed> A record
     * @return string A record key
     */
    public function createKeyForRecord(array $record): string
    {
        $key = $this->schema->createKeyForRecord($record);
        if (!$key) {
            throw new \Exception('Could not create a default record key');
        }
        return $key;
    }


    /* *********************
     * Field related methods
     * *********************/

    // TODO rename method to getFields(), rename getFields() to getQueryFields()
    /**
     * @return array<Field>
     */
    public function getFields2(): array
    {
        return $this->schema->getFields2();
    }

    /**
     * Get a Field instance for the key.
     *
     * @param ?int $exceptId Record ID that will be ignored when the validator checks for conflicts
     * @return Field
     */
    public function getKeyField(?int $exceptId = null): Field
    {
        return new Field(
            table: $this->name,
            name: 'key',
            label: 'Key',
            validators: [fn() => new NewKeyValidator($this->store, $exceptId)],
        );
    }

    /**
     * @return array<QueryField>
     */
    public function getFields(): array
    {
        return $this->schema->getQueryFields();
    }

    /**
     * @param string $name
     * @return QueryField
     */
    public function getField(string $name): QueryField
    {
        return $this->getFields()[$name];
    }

    /**
     * @return array<string>
     */
    public function getFieldNames(): array
    {
        return array_keys($this->getFields());
    }

    /**
     * @param array<string> $fields
     * @return array<string> List of invalid field names
     */
    public function checkFieldNames(array $fields): array
    {
        return array_filter($fields, fn ($field) => !in_array($field, $this->getFieldNames()));
    }

    /**
     * @param null|array<string> $fields
     * @return array<string,string>
     */
    public function getFieldLabels(array $fields = null): array
    {
        if (!$fields) {
            $labels = array_combine(
                $this->getFieldNames(),
                array_column($this->getFields(), 'label')
            );
        } else {
            $labels = [];
            foreach ($fields as $field) {
                $labels[$field] = $this->getField($field)->label;
            }
        }
        return $labels;
    }

    /**
     * Get a list of default fields for the list view.
     *
     * @return array<string>
     */
    public function getDefaultListFields(): array
    {
        return $this->schema->getDefaultListFields();
    }


    /* *********************
     * Query related methods
     * *********************/

    /**
     * @param array<string> $fields
     * @param array<string,string> $orderBy
     * @param array<array{string,string,string}> $filters
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
        return $qb->getQuery()->fetch();
    }

    /**
     * Find a record by its key or ID.
     *
     * @param string|int $keyOrId Key or ID of a record
     * @return array|null Either a record if one is found or null
     */
    public function findByKeyOrId(string|int $keyOrId): ?array
    {
        return ctype_digit($keyOrId)
            ? $this->store->findById($keyOrId)
            : $this->store->findOneBy(['key', '=', $keyOrId]);
    }

    /**
     * Get all filters.
     *
     * @return array<QueryFilter>
     */
    public function getFilters(): array
    {
        $filters = [];
        foreach ($this->schema->getFilters() as $fieldName => $fieldFilters) {
            foreach ($fieldFilters as $fieldFilter) {
                $filters[] = $fieldFilter;
            }
        }
        return $filters;
    }

    protected function getFilter(string $field, string $operator): QueryFilter
    {
        return $this->schema->getFilters()[$field][$operator];
    }

    protected function modifyQueryForField(QueryBuilder $qb, string $fieldName): QueryBuilder
    {
        $field = $this->getField($fieldName);
        return $field->modifyQuery($qb, $fieldName, $this->db);
    }

    protected function modifyQueryForFilter(QueryBuilder $qb, string $fieldName, string $operator, mixed $fieldValue): QueryBuilder
    {
        $filter = $this->getFilter($fieldName, $operator);
        return $filter->modifyQuery($qb, $fieldValue, $this->db);
    }


    /* *************************
     * Reference related methods
     * *************************/

    /**
     * @return array<Reference>
     */
    public function getReferences(): array
    {
        return $this->schema->getReferences();
    }

    /**
     * Find records referring the specified record
     *
     * @param string $key Key of the references record
     * @return array<string,array<string,mixed>> Key is {type}.{field}, value is the referring record
     */
    public function findReferringRecords(string $key): array
    {
        $records = [];
        $references = $this->findReferences($this->name);
        foreach ($references as $reference) {
            $referringStore = $this->db->getTable($reference->table)->store;
            $operator = $reference->multiple
                ? 'CONTAINS'
                : '=';
            $referringRecords = $referringStore->createQueryBuilder()
                ->select(['id', 'key'])
                ->where([$reference->foreignField, $operator, $key])
                ->getQuery()
                ->fetch();
            if (!empty($referringRecords)) {
                $records[sprintf('%s.%s', $reference->table, $reference->foreignField)] = $referringRecords;
            }
        }
        return $records;
    }

    /**
     * @param string $targetTableName
     * @return array<Reference>
     */
    private function findReferences(string $targetTableName): array
    {
        $references = [];
        foreach ($this->db->getTables() as $originTable) {
            $originReferences = $originTable->getReferences();
            foreach ($originReferences as $originFieldName => $originReference) {
                if ($originReference->foreignTable === $targetTableName) {
                    $references[] = $originReference;
                }
            }
        }
        return $references;
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
     * @return array<string,mixed>
     */
    public function getDefaultsFromAutocompleteInput(string $value): array
    {
        return $this->schema->getDefaultsFromAutocompleteInput($value);
    }
}
