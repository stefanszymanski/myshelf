<?php

declare(strict_types=1);

namespace App\Validator;

use Nicebooks\Isbn\IsbnTools;

/**
 * Validate for an ISBN-10.
 */
class Isbn10Validator extends AbstractValidator
{
    protected function isValid(mixed $value): mixed
    {
        $isbnTools = new IsbnTools;
        if (!$isbnTools->isValidIsbn10($value)) {
            throw new ValidationException('Not a valid ISBN-10');
        }
        return $isbnTools->format($value);
    }
}
