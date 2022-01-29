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
    public function modifyResult(array $result, string $alias, ?string $queryFieldPath): array
    {
        return $result;
    }

    /**
     * Get the label.
     *
     * @return string
     */
    public function getLabel(string $alias, ?string $queryFieldPath, Database $db, Table $table): string
    {
        return $this->label;
    }
}
