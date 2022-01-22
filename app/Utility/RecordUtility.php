<?php

namespace App\Utility;

class RecordUtility
{
    /**
     * Convert a value into a string.
     *
     * Used for displaying field values.
     *
     * @param mixed $value
     * @return string
     */
    public static function convertToString($value): string
    {
        if (is_array($value) && array_is_list($value)) {
            return implode("\n", $value);
        } elseif (is_array($value)) {
            $value = array_filter($value);
            return implode("\n", array_map(
                fn ($key, $value) => "$key: $value",
                array_keys($value),
                array_values($value)
            ));
        } elseif (is_string($value) || is_numeric($value)) {
            return $value;
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_null($value)) {
            return '';
        } elseif (is_object($value)) {
            return get_class($value);
        } else {
            return gettype($value);
        }
    }
}
