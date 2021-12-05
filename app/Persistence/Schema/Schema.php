<?php

namespace App\Persistence\Schema;

use App\Persistence\Field;
use App\Persistence\Query\Field as QueryField;
use App\Persistence\Query\Filter as QueryFilter;
use SleekDB\Store;

interface Schema
{
    /**
     * Get the label of the schema/table.
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Get fields.
     *
     * @return array<Field>
     */
    public function getFields(): array;

    /**
     * Get all fields.
     *
     * @return array<QueryField>
     */
    public function getQueryFields(): array;

    /**
     * Get all filters.
     *
     * @return array<string,array<string,QueryFilter>>
     */
    public function getQueryFilters(): array;

    /**
     * Get a default list of fields to use in the list view.
     *
     * @return array<string> List of field names
     */
    public function getDefaultListFields(): array;

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
     * @return array<string,mixed>
     */
    public function getDefaultsFromAutocompleteInput(string $value): array;

    /**
     * Create a default record key from record fields.
     *
     * @param array<string,mixed> $record
     * @return string A record key
     */
    public function createKeyForRecord(array $record): string;
}
