<?php

namespace App\Validator;

use SleekDB\Store;

/**
 * Validate a new key.
 *
 * The key must not be empty and must be unique inside a given store.
 */
class NewKeyValidator extends AbstractValidator
{
    protected bool $allowEmpty = false;

    public function __construct(protected Store $store)
    {
    }

    protected function isValid($value)
    {
        if ($this->store->findOneBy(['key', '=', $value])) {
            throw new \Exception('This key is already used.');
        }
        return $value;
    }
}
