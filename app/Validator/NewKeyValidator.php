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

    public function __construct(protected Store $store, protected ?int $except = null)
    {
    }

    protected function isValid(mixed $value): mixed
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException('Argument $value must be a string');
        }
        $criteria = ['key', '=', $value];
        if ($this->except) {
            $criteria = [$criteria, ['id', '!==', $this->except]];
        }
        if ($this->store->findOneBy($criteria)) {
            throw new ValidationException('This key is already used.');
        }
        return $value;
    }
}
