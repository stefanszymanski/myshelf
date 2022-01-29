<?php

declare(strict_types=1);

namespace App\Validator;

use Nicebooks\Isbn\IsbnTools;

/**
 * Validate for an ISBN-13.
 */
class Isbn13Validator extends AbstractValidator
{
    protected function isValid(mixed $value): mixed
    {
        $isbnTools = new IsbnTools;
        if (!$isbnTools->isValidIsbn13($value)) {
            throw new ValidationException('Not a valid ISBN-13');
        }
        return $isbnTools->format($value);
    }
}
