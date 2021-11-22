<?php

namespace App\Domain\Type;

abstract class AbstractType implements TypeInterface
{
    // TODO switch to PHP 8.1 and use an enum
    public const FIELD_TYPE_NORMAL = 'normal';
    public const FIELD_TYPE_VIRTUAL = 'virtual';
    public const FIELD_TYPE_JOIN = 'join';

    protected array $fields = [];

    public function __construct()
    {
        $this
            ->registerField(
                name: 'id',
                label: 'ID',
                description: 'Auto generated unique numeric ID'
            )
            ->registerField(
                name: 'key',
                label: 'Key',
                description: 'Unique human-readable key'
            );
        $this->configure();
    }

    abstract protected function configure(): void;

    public function getFieldNames(bool $includeNormal = true, bool $includeVirtual = true, $includeJoin = true): array
    {
        $fieldNames = [];
        foreach ($this->fields as $name => $field) {
            if ($includeNormal && $field['type'] === self::FIELD_TYPE_NORMAL) {
                $fieldNames[] = $name;
                continue;
            }
            if ($includeVirtual && $field['type'] === self::FIELD_TYPE_VIRTUAL) {
                $fieldNames[] = $name;
                continue;
            }
            if ($includeJoin && $field['type'] === self::FIELD_TYPE_JOIN) {
                $fieldNames[] = $name;
                continue;
            }
        }
        return $fieldNames;
    }

    public function checkFieldNames(string ...$fields): ?array
    {
        return array_filter($fields, fn ($field) => !in_array($field, array_keys($this->fields))) ?? null;
    }

    public function getFieldLabels(string ...$fields): array
    {
        $labels = [];
        foreach ($fields as $field) {
            $labels[$field] = $this->fields[$field]['label'];
        }
        return $labels;
    }

    public function getFieldConfiguration(string $fieldName): array
    {
        return $this->fields[$fieldName];
    }

    protected function registerField(string $name, string $label, string $description = null): self
    {
        $this->fields[$name] = [
            'type' => self::FIELD_TYPE_NORMAL,
            'label' => $label,
            'description' => $description,
            'select' => [$name]
        ];
        return $this;
    }

    protected function registerVirtualField(string $name, string $label, array $select, string $description = null): self
    {
        $this->fields[$name] = [
            'type' => self::FIELD_TYPE_VIRTUAL,
            'label' => $label,
            'description' => $description,
            'select' => [$name => $select]
        ];
        return $this;
    }

    protected function registerJoinField(string $name, string $label, string|array|callable $select, string $joinAs, callable $join, string $description = null)
    {
        $this->fields[$name] = [
            'type' => self::FIELD_TYPE_JOIN,
            'label' => $label,
            'description' => $description,
            'select' => [$name => $select],
            /* 'select' => [$joinAs], */
            'join' => $join,
            'joinAs' => $joinAs,
        ];
        return $this;
    }
}
