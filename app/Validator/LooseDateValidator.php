<?php

namespace App\Validator;

/**
 * Validate for a date or just a a part of it.
 *
 * The value must be of one of the following formats:
 * - {year}-{month}-{day}
 * - {year}-{month}
 * - {year}
 *
 * If the month or day is missing, it is set to '00' in the returned string.
 * E.g. "2021-10" becomes "2021-10-01"
 *
 * Also month and day get prefixed with "0" if they have just one digit.
 * E.g. "2021-5-3" becomes "2021-05-03"
 */
class LooseDateValidator extends AbstractValidator
{
    protected function isValid(mixed $value): mixed
    {
        if (!is_string($value) && !is_integer($value)) {
            throw new \InvalidArgumentException('Argument $value must be a string or an integer');
        }
        $value = (string) $value;

        if (!preg_match('/^(-?[0-9]+)(-([0-9]{1,2})(-([0-9]{1,2}))?)?$/', $value, $matches)) {
            throw new ValidationException('The answer must be either "year", "year-month" or "year-month-day".');
        }

        $parts = array_filter([
            $matches[1],
            $matches[3] ?? null,
            $matches[5] ?? null,
        ]);

        switch (count($parts)) {
            case 1:
                list($year) = $parts;
                $result = $year;
                break;
            case 2:
                list($year, $month) = $parts;
                if ($month > 12) {
                    throw new ValidationException('The answer must be either "year", "year-month" or "year-month-day".');
                }
                $result = sprintf('%s-%s', $year, str_pad($month, 2, '0', STR_PAD_LEFT));
                break;
            case 3:
                list($year, $month, $day) = $parts;
                if (!checkdate((int) $month, (int) $day, (int) $year)) {
                    throw new ValidationException('The answer must be either "year", "year-month" or "year-month-day".');
                }
                $result = sprintf('%s-%s-%s', $year, str_pad($month, 2, '0', STR_PAD_LEFT), str_pad($day, 2, '0', STR_PAD_LEFT));
                break;
            default:
                throw new ValidationException('The answer must be either "year", "year-month" or "year-month-day".');
        }

        return $result;
    }
}
