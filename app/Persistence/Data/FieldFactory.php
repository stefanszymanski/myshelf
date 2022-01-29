<?php

declare(strict_types=1);

namespace App\Persistence\Data;

use App\Persistence\Data\InputField;
use App\Persistence\Data\ListField;
use App\Persistence\Data\ReferenceField;
use App\Persistence\Data\SelectField;
use App\Validator\ConjunctionValidator;
use App\Validator\IntegerValidator;
use App\Validator\Isbn10Validator;
use App\Validator\Isbn13Validator;
use App\Validator\LooseDateValidator;
use App\Validator\NotEmptyValidator;
use App\Validator\NullValidator;

class FieldFactory
{
    /**
     * Create an input field for a string value.
     *
     * @param string $label
     * @param bool $required
     * @return Field
     */
    public static function string(string $label, bool $required = false): Field
    {
        $validator = $required ? new NotEmptyValidator : new NullValidator;
        return new InputField($validator, $label);
    }

    /**
     * Create an input fiel for an integer value.

     * @param string $label
     * @param bool $required
     * @param int|null $minimum
     * @param int|null $maximum
     * @return Field
     */
    public static function integer(string $label, bool $required = false, int $minimum = null, int $maximum = null): Field
    {
        $integerValidator = new IntegerValidator($minimum, $maximum);
        $validator = $required
            ? new ConjunctionValidator([new NotEmptyValidator, $integerValidator])
            : $integerValidator;
        return new InputField($validator, $label);
    }

    /**
     * Create an input field for an ISBN-10.
     *
     * @param string $label
     * @param bool $required
     * @return Field
     */
    public static function isbn10(string $label, bool $required = false): Field
    {
        $isbnValidator = new Isbn10Validator;
        $validator = $required
            ? new ConjunctionValidator([new NotEmptyValidator, $isbnValidator])
            : $isbnValidator;
        return new InputField($validator, $label);
    }

    /**
     * Create an input field for an ISBN-13.
     *
     * @param string $label
     * @param bool $required
     * @return Field
     */
    public static function isbn13(string $label, bool $required = false): Field
    {
        $isbnValidator = new Isbn13Validator;
        $validator = $required
            ? new ConjunctionValidator([new NotEmptyValidator, $isbnValidator])
            : $isbnValidator;
        return new InputField($validator, $label);
    }

    /**
     * Create an input field for a loose date.
     *
     * @param string $label
     * @param bool $required
     * @return Field
     */
    public static function looseDate(string $label, bool $required = false): Field
    {
        $looseDateValidator = new LooseDateValidator;
        $validator = $required
            ? new ConjunctionValidator([new NotEmptyValidator, $looseDateValidator])
            : $looseDateValidator;
        return new InputField($validator, $label);
    }

    /**
     * Create a select field with a fixed list of options.
     *
     * @param array<string,string> $options
     * @param string $label
     * @param bool $required
     * @return Field
     */
    public static function select(array $options, string $label, bool $required = false): Field
    {
        $validator = $required ? new NotEmptyValidator : new NullValidator;
        return new SelectField($options, $validator, $label);
    }

    /**
     * Wrap another field to make it a multivalue field.
     *
     * @param Field $field
     * @param bool $sortable
     * @param bool $required
     * @return Field
     */
    public static function list(Field $field, bool $sortable = false, bool $required = false): Field
    {
        $validator = $required ? new NotEmptyValidator : new NullValidator;
        return new ListField($field, $sortable, $validator);
    }

    /**
     * Create a field referring a record of another table.
     *
     * @param string $targetTable
     * @param string $label
     * @param bool $required
     * @return Field
     */
    public static function reference(string $targetTable, string $label, bool $required = false): Field
    {
        $validator = $required ? new NotEmptyValidator : new NullValidator;
        return new ReferenceField($targetTable, $validator, $label);
    }

    /**
     * Create a field referring to multiple records of another table.
     *
     * @param string $targetTable
     * @param string $label
     * @param bool $required
     * @param bool $sortable
     * @return Field
     */
    public static function references(string $targetTable, string $label, bool $required = false, bool $sortable = false): Field
    {
        return self::list(self::reference($targetTable, $label), $sortable, $required);
    }

    /**
     * Create a field containing sub fields.
     *
     * @param array<string,Field> $fields
     * @param string $label
     * @param \Closure $formatter
     * @return Field
     */
    public static function struct(array $fields, string $label, \Closure $formatter = null): Field
    {
        $validator = new NullValidator;
        return new StructField($fields, $validator, $label, $formatter);
    }
}
