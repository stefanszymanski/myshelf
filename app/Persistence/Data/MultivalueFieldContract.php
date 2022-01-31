<?php

declare(strict_types=1);

namespace App\Persistence\Data;

interface MultivalueFieldContract
{
    /**
     * Get the data field for an element.
     *
     * @return Field
     */
    public function getField(): Field;

    /**
     * Get whether the elements are sortable
     *
     * @return bool
     */
    public function isSortable(): bool;
}
