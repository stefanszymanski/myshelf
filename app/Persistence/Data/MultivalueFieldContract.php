<?php

declare(strict_types=1);

namespace App\Persistence\Data;

interface MultivalueFieldContract
{
    public function getField(): Field;

    /**
     * Get whether the elements are sortable
     *
     * @return bool
     */
    public function isSortable(): bool;
}
