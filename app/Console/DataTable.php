<?php

declare(strict_types=1);

namespace App\Console;

use App\Persistence\Data\Field;
use Symfony\Component\Console\Helper\Table as SymfonyTable;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\OutputInterface;

class DataTable
{
    /**
     * @var array<string,mixed>
     */
    protected array $data;

    /**
     * @var array<string,mixed>
     */
    protected ?array $newData = null;

    /**
     * @var array<string,Field>
     */
    protected array $fields;

    /**
     * Whether the ID field should be displayed.
     *
     * @var bool
     */
    protected bool $displayIdField = false;

    /**
     * Whether the Key field should be displayed.
     *
     * @var bool
     */
    protected bool $displayKeyField = false;

    /**
     * Whether a row for numbering the fields should be displayed.
     *
     * @var bool
     */
    protected bool $displayFieldNumberRow = false;

    public function __construct(protected OutputInterface $output)
    {
    }

    /**
     * Set data.
     *
     * @param array<string,mixed> $data
     * @return self
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Set new data.
     *
     * @param array<string,mixed> $newData
     * @return self
     */
    public function setNewData(?array $newData): self
    {
        $this->newData = $newData;
        return $this;
    }

    /**
     * Set fields that should be displayed.
     *
     * The fields in the table are rendered in the ordered as the fields are given.
     *
     * @param array<string,Field> $fields
     * @return self
     */
    public function setFields(array $fields): self
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * Set whether the ID field should be displayed.
     *
     * @param bool $shouldDisplay
     * @return self
     */
    public function setDisplayIdField(bool $shouldDisplay): self
    {
        $this->displayIdField = $shouldDisplay;
        return $this;
    }

    /**
     * Set whether a column with a number for each field should be displayed.
     *
     * @param bool $shouldDisplay
     * @return self
     */
    public function setDisplayFieldNumberColumn(bool $shouldDisplay): self
    {
        $this->displayFieldNumberRow = $shouldDisplay;
        return $this;
    }

    /**
     * Render the table.
     *
     * @return void
     */
    public function render(): void
    {
        if (!$this->fields) {
            return;
        }

        $dataRows = $this->createDataRows();
        $idRows = $this->createIdRows();
        $headers = $this->createHeaders();

        $rows = !empty($idRows)
            ? [...$idRows, new TableSeparator(), ...$dataRows]
            : $dataRows;

        $highlightedColumn = $this->displayFieldNumberRow ? 1 : 0;

        $this->renderTable($headers, $rows, $highlightedColumn);
    }

    /**
     * Create table rows for the fields.
     *
     * @return array<array<string>>
     */
    protected function createDataRows(): array
    {
        // Build rows for the data fields (i.e. all fields except id and key).
        $rows = [];
        $i = 0;
        foreach ($this->fields as $fieldName => $field) {
            $value = $field->formatValue($this->data[$fieldName] ?? null);
            $row = [$field->getLabel(), $value];
            // Prepend a column for the field number.
            if ($this->displayFieldNumberRow) {
                array_unshift($row, $i + 1);
            }
            // Append a column for the changed value.
            if ($this->newData) {
                $newValue = $field->formatValue($this->newData[$fieldName] ?? null);
                $row[] = $this->formatNewValue($value, $newValue);
            }
            $rows[] = $row;

            $i++;
        }
        return $rows;
    }

    /**
     * Create table rows for the ID and Key field.
     *
     * @return array<array<string>>
     */
    protected function createIdRows(): array
    {
        $rows = [];
        if ($this->displayIdField && isset($this->data['id'])) {
            $row = ['ID', $this->data['id'] ?? null];
            if ($this->displayFieldNumberRow) {
                array_unshift($row, '');
            }
            array_unshift($rows, $row);
        }
        return $rows;
    }

    /**
     * Create table headers.
     *
     * @return array<string>
     */
    protected function createHeaders(): array
    {
        $headers = ['Field', 'Value'];
        if ($this->displayFieldNumberRow) {
            array_unshift($headers, '#');
        }
        if ($this->newData) {
            $headers[] = 'New value';
        }
        return $headers;
    }

    /**
     * Format the new value depending on how it differs from the original value
     *
     * @param mixed $value
     * @param mixed $newValue
     * @return string
     */
    protected function formatNewValue(mixed $value, mixed $newValue): string
    {
        // TODO highlight changed elements in multivalue fields, e.g. in multireference fields
        $format = match (true) {
            $value === $newValue => '%s',
            empty($value) && !empty($newValue) => '<added>%s</>',
            !empty($value) && empty($newValue) => '<removed>deleted</>',
            $value !== $newValue => '<changed>%s</>',
            default => throw new \UnexpectedValueException('This should not happen'),
        };
        return sprintf($format, $newValue);
    }

    /**
     * Render the table.
     *
     * @param array<string> $headers Table headers
     * @param array<array<string>|TableSeparator> $rows Table rows
     * @param int $highlightColumn Number of a column that should be hightlighted
     * @return void
     */
    protected function renderTable(array $headers, array $rows, ?int $highlightColumn = null): void
    {
        if (is_int($highlightColumn)) {
            // Highlight the content of a specific column.
            $rows = array_map(
                fn ($row) => is_array($row)
                    ? [
                        ...array_slice($row, 0, $highlightColumn),
                        sprintf('<thead>%s</>', $row[$highlightColumn]),
                        ...array_slice($row, $highlightColumn + 1)
                    ]
                    : $row,
                $rows
            );
        }
        $table = new SymfonyTable($this->output);
        $table->setStyle('box');
        $table->getStyle()->setCellHeaderFormat('<thead>%s</>');
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
    }
}
