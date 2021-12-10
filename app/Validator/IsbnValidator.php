<?php

declare(strict_types=1);

namespace App\Validator;

use Nicebooks\Isbn\IsbnTools;

/**
 * Validate for an ISBN.
 */
class IsbnValidator extends AbstractValidator
{
    protected function isValid(mixed $value): mixed
    {
        $isbnTools = new IsbnTools;
        if (!$isbnTools->isValidIsbn($value)) {
            throw new ValidationException('Not a valid ISBN');
        }
        return $isbnTools->format($value);
    }
}
