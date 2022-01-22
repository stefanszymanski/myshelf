<?php

declare(strict_types=1);

namespace App\Persistence\Data;

interface ReferenceFieldContract
{
    public function getReferredTableName(): string;
}
