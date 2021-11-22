<?php

namespace App\Domain\Type;

interface TypeInterface
{
    public function getFieldNames(bool $includeNormal = true, bool $includeVirtual = true, $includeJoin = true): array;

    public function checkFieldNames(string ...$fields): ?array;
}
