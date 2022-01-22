<?php

declare(strict_types=1);

namespace App\Persistence\Data;

class References
{
    /**
     * List of tuples each containing a table name and a field name.
     *
     * @var array<array{string,string}>
     */
    protected array $references = [];

    /**
     * Add a reference.
     *
     * @param string $targetTable
     * @return void
     */
    public function add(string $targetTable): void
    {
        $this->references[] = [$targetTable, ''];
    }

    /**
     * Prepend the field names of references with another field name.
     *
     * @param string $fieldName
     * @return void
     */
    public function indent(string $fieldName): void
    {
        $this->references = array_map(
            fn ($ref) => [$ref[0], $ref[1] ? sprintf('%s.%s', $fieldName, $ref[1]) : $fieldName],
            $this->references,
        );
    }

    /**
     * Merge with another References objet.
     *
     * @param References $otherReferences
     * @return void
     */
    public function merge(self $otherReferences): void
    {
        $this->references = array_merge($this->references, $otherReferences->get());
    }

    /**
     * Get the list of reference tuples
     *
     * @return array<array{string,string}> Tuples of table name and field name
     */
    public function get(): array
    {
        return $this->references;
    }
}
