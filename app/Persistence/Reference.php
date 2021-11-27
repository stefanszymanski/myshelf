<?php

declare(strict_types=1);

namespace App\Persistence;

class Reference
{
    public function __construct(
        public readonly string $table,
        public readonly string $foreignTable,
        public readonly string $foreignField,
        public readonly bool $multiple = false,
    ) {
    }
}
