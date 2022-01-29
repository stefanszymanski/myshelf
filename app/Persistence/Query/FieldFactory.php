<?php

declare(strict_types=1);

namespace App\Persistence\Query;

class FieldFactory
{
    /**
     * Select the value from a data field.
     *
     * @param string $fieldName
     * @param string $label
     * @return Field
     */
    public static function forDatafield(string $fieldName, string $label): Field
    {
        return new DataField($fieldName, $label);
    }

    /**
     * Select the value of of the first non empty query field.
     *
     * @param array<string> $queryFieldNames Names of query fields to try to get a value from
     * @param string $label
     * @return Field
     */
    public static function alternatives(array $queryFieldNames, string $label): Field
    {
        return new AlternativesField($queryFieldNames, $label);
    }

    /**
     * Concatinate multiple data fields.
     *
     * @param string $separator
     * @param array<string> $fieldNames
     * @param string $label
     * @return Field
     */
    public static function concat(string $separator, array $fieldNames, string $label): Field
    {
        $fieldNames = array_map(self::normalizeDataFieldName(...), $fieldNames);
        $select = ['CONCAT' => [$separator, ...$fieldNames]];
        return new SelectField($select, $label);
    }

    /**
     * Count foreign references.
     *
     * @param string $tableName
     * @param string $fieldName
     * @param string $label
     * @return Field
     */
    public static function countReferences(string $tableName, string $fieldName, string $label): Field
    {
        return new ReferencesField(
            $tableName,
            $fieldName,
            fn ($records) => count($records),
            $label
        );
    }

    /**
     * Read the raw value of a field.
     *
     * @param string $fieldName
     * @param string $label
     * @return Field
     */
    public static function raw(string $fieldName, string $label): Field
    {
        return new RawField($fieldName, $label);
    }

    protected static function normalizeDataFieldName(string $fieldName): string
    {
        if (empty($fieldName)) {
            throw new \InvalidArgumentException('A field name must not be empty.');
        }
        return "data.$fieldName";
    }
}
