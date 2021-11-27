<?php

namespace App\Persistence\Schema;

use SleekDB\Store;

interface Schema
{
    public function getLabel(): string;
    public function getFields(): array;
    public function getFilters(): array;
    public function getReferences(): array;

    /**
     * Get a default list of fields to use in the list view.
     *
     * @return array<string> List of field names
     */
    public function getDefaultListFields(): array;

    public function getAutocompleteOptions(Store $store): array;

    public function getDefaultsFromAutocompleteInput(string $value): array;
}
