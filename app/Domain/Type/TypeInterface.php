<?php

namespace App\Domain\Type;

use App\Database;
use SleekDB\QueryBuilder;
use SleekDB\Store;

interface TypeInterface
{
    /**
     * Get a list of supported field names.
     *
     * @return array<string> List of field names
     */
    public function getFieldNames(): array;

    /**
     * Get a default list of fields to use in the list view.
     *
     * @return array<string> List of field names
     */
    public function getDefaultListFields(): array;

    /**
     * Check the given field names for invalid ones.
     *
     * @param array<string> $fields List of field names to check
     * @return array<string> List of invalid field names
     */
    public function checkFieldNames(array $fields): array;

    /**
     * Get labels for the given field names.
     *
     * @param array<string> Field names
     * @return array<string,string> Keys are field names, values are their labels
     */
    public function getFieldLabels(array $fields): array;

    /**
     * Get info about all fields.
     *
     * @return array<string,array{name:string,label:string,description:string,type:string}>
     */
    public function getFieldInfo(): array;

    /**
     * Get info about all filters.
     *
     * @return array<string,array{name:string,operator:string,description:string}>
     */
    public function getFilterInfo(): array;

    /**
     * Modifies the given QueryBuilder with a filter that is determined by the arguments $fieldName and $operator.
     *
     * @param Database $db
     * @param QueryBuilder $qb
     * @param string $fieldName
     * @param string $operator
     * @param mixed $fieldValue
     * @return QueryBuilder The same object as given or a new one.
     */
    public function modifyQueryForFilter(Database $db, QueryBuilder $qb, string $fieldName, string $operator, $fieldValue): QueryBuilder;

    /**
     * Modifies a given QueryBuilder to include the given field.
     *
     * @param Database $db
     * @param QueryBuilder $qb
     * @param string $fieldName
     * @return QueryBuilder The same object as given or a new one.
     */
    public function modifyQueryForField(Database $db, QueryBuilder $qb, string $fieldName): QueryBuilder;

    public function getAutocompleteOptions(Store $store): array;

    public function getDefaultsFromAutocompleteInput(string $value): array;
}
