<?php

declare(strict_types=1);

namespace App\Persistence\Data;

interface ContainerFieldContract
{
    /**
     * @return array<string,Field>
     */
    public function getSubFields(): array;

    /**
     * @param string $subFieldName
     * @return Field
     */
    public function getSubField(string $subFieldName): Field;
}
