<?php

namespace App\Persistence\Schema;

use App\Persistence\Data\Field as DataField;
use App\Persistence\Database;
use App\Persistence\Query\Field as QueryField;
use App\Persistence\Query\Filter as QueryFilter;
use App\Persistence\Table;
use SleekDB\Store;

interface Schema
{
    /**
     * Get data fields.
     *
     * @return array<string, DataField>
     */
    public function getDataFields(): array;

    /**
     * Get query fields.
     *
     * @return array<string,QueryField>
     */
    public function getQueryFields(): array;

    /**
     * Get query filters.
     *
     * @return array<string,QueryFilter>
     */
    public function getQueryFilters(): array;

    /**
     * Get a query filter.
     *
     * @param string $fieldName
     * @param string $operator
     * @return QueryFilter
     * @throws \InvalidArgumentException if there is no filter for `$fieldName` and `$operator`.
     */
    public function getQueryFilter(string $fieldName, string $operator): QueryFilter;

    /**
     * Get a default list of fields to use in the list view.
     *
     * @return array<string> List of field names
     */
    public function getDefaultListFields(): array;

    /**
     * Get names of data fields to ask for in the New Record Dialog.
     *
     * @return array<string>
     */
    public function getNewRecordDialogFieldNames(): array;

    /**
     * Get the title for a record.
     *
     * @param record $record
     * @param Table $table
     * @param Database $database
     * @return string
     */
    public function getRecordTitle(array $record, Table $table, Database $db): string;

    /**
     * Get autocomplete options for a record selection dialog.
     *
     * @param Store $store
     * @return array<string,string> Key is the autocomplete text, value is a record key
     */
    public function getAutocompleteOptions(Store $store): array;

    /**
     * Parse the user input from record selection dialog into record default values.
     *
     * @param string $value
     * @return record
     */
    public function getDefaultsFromAutocompleteInput(string $value): array;
}
