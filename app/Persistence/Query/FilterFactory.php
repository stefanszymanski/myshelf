<?php

declare(strict_types=1);

namespace App\Persistence\Query;

use InvalidArgumentException;

class FilterFactory
{
    protected const OPERATORS = [
        ['=', '=='],
        ['!=', '!='],
        ['~', 'LIKE'],
        ['>', '>'],
        ['<', '<'],
        ['>=', '>='],
        ['<=', '<='],
        ['#', 'IN'],
    ];


    /**
     * Create filters for a query field.
     *
     * @param string $fieldName
     * @param bool $equal
     * @param bool $unequal
     * @param bool $like
     * @param bool $gt
     * @param bool $lt
     * @param bool $gte
     * @param bool $lte
     * @param bool $in
     * @return Filter
     * @throws InvalidArgumentException when none of the boolean arguments is true.
     *
     * TODO add IN-Filter
     */
    public static function forField(
        string $fieldName,
        bool $equal = false,
        bool $unequal = false,
        bool $like = false,
        bool $gt = false,
        bool $lt = false,
        bool $gte = false,
        bool $lte = false,
        bool $in = false,
    ): Filter {
        if (!($equal || $unequal || $like || $gt || $lt || $gte || $lte || $in)) {
            throw new InvalidArgumentException('At least one argument must be true');
        }
        $operators = [];
        foreach ([$equal, $unequal, $like, $gt, $lt, $gte, $lte, $in] as $i => $enabled) {
            if ($enabled) {
                list($operator, $sleekDbOperator) = self::OPERATORS[$i];
                $operators[$operator] = $sleekDbOperator;
            }
        }
        return new FieldFilter($fieldName, $operators);
    }

    /**
     * Create a filter for fields of referred records.
     *
     * @param string $dataFieldName
     * @param string $targetTableName
     * @param bool $isMultivalue
     * @return Filter
     */
    public static function forReference(string $dataFieldName, string $targetTableName, bool $isMultivalue = false): Filter
    {
        return new ReferenceFilter($dataFieldName, $targetTableName, $isMultivalue);
    }
}
