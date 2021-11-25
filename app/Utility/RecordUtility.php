<?php

namespace App\Utility;

class RecordUtility
{
    static public function createKey(?string ...$parts): string
    {
        // TODO replace umlaute and similar characters instead of removing them
        // TODO remove doubled dashes
        $parts = array_filter($parts);
        $key = strtolower(implode('-', $parts));
        $key = preg_replace('/[^a-z0-9 -]/', '', $key);
        $key = preg_replace('/[ ]/', '-', $key);
        return $key;
    }

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
            return implode(', ', $value);
        } elseif (is_array($value)) {
            return json_encode($value);
        } elseif (is_string($value) || is_numeric($value)) {
            return $value;
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_null($value)) {
            return '';
        } else {
            return gettype($value);
        }
    }
}
