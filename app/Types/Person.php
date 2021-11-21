<?php

namespace App\Types;

class Person
{
    protected const FIELDS = [
        'id' => [
            'label' => 'ID'
        ],
        'key' => [
            'label' => 'Key',
            'select' => ['key']
        ],
        'firstname' => [
            'label' => 'First name',
            'select' => ['firstname']
        ],
        'lastname' => [
            'label' => 'Last name',
            'select' => ['lastname']
        ],
        'nationality' => [
            'label' => 'Nationality',
            'select' => ['nationality']
        ],
        'name' => [
            'label' => 'Full name',
            'select' => ['name' => ['CONCAT' => [', ', 'lastname', 'firstname']]]
        ],
        'name2' => [
            'label' => 'Full name',
            'select' => ['name2' => ['CONCAT' => [' ', 'firstname', 'lastname']]]
        ],
    ];

    static public function getNames(): array
    {
        return array_keys(static::FIELDS);
    }

    static public function getSelect(array $fieldNames)
    {
        $select = [];
        foreach ($fieldNames as $fieldName) {
            if (!array_key_exists('select', static::FIELDS[$fieldName])) {
                continue;
            }
            $select = array_merge($select, static::FIELDS[$fieldName]['select']);
        }
        return $select;
    }

    static public function getLabels(array $fieldNames)
    {
        $labels = [];
        foreach ($fieldNames as $fieldName) {
            $labels[$fieldName] = static::FIELDS[$fieldName]['label'];
        }
        return $labels;
    }
}
