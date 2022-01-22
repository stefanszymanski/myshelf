<?php

declare(strict_types=1);

namespace App\Persistence\Query;

use App\Persistence\Database;
use App\Persistence\Table;

abstract class AbstractField implements Field
{
    /**
     * @param string $label
     */
    public function __construct(
        protected readonly string $label,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getSubQueryField(string $queryFieldName, Database $db, Table $table): Field
    {
        throw new \Exception('Sub fields are not supported by ' . self::class);
    }

    /**
     * Get the label.
     *
     * @return string
     */
    public function getLabel(?string $fieldName = null): string
    {
        return $this->label;
    }
}
