<?php

namespace App\Utility;

use Symfony\Component\String\UnicodeString;

class RecordUtility
{
    static public function createKey(?string ...$parts): string
    {
        $parts = array_filter($parts);
        $key = strtolower(implode('-', $parts));
        $key = (new UnicodeString($key))->ascii();
        $key = preg_replace('/-+/', '-', $key);
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
        } else {
            return gettype($value);
        }
    }
}
