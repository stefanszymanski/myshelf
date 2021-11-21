<?php

namespace App\Utility;

class RecordUtility
{
    static public function createKey(string ...$parts): string
    {
        $key = strtolower(implode('-', $parts));
        $key = preg_replace('/[^a-z0-9 -]/', '', $key);
        $key = preg_replace('/[ ]/', '-', $key);
        return $key;
    }
}
