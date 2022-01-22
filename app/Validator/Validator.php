<?php

declare(strict_types=1);

namespace App\Validator;

interface Validator
{
    public function validate(mixed $value): mixed;

    public function __invoke(mixed $value): mixed;
}
