<?php

declare(strict_types=1);

namespace App\Console;

use App\Persistence\Field;
use App\Persistence\Table;
use Symfony\Component\Console\Helper\Table as SymfonyTable;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// TODO split into classes RecordTable and RecordEditTable
class RecordView
{
    public function __construct(protected InputInterface $input, protected SymfonyStyle $output, protected Table $table)
    {
    }

    /**
     * Render a record as table.
     *
     * @param array<string,mixed> $record
     * @return void
     */
    public function renderTable(array $record): void
    {
        $fields = $this->table->getFields2();
        // Build rows for the data fields (i.e. all fields except id and key).
        $dataRows = array_map(
            fn (Field $field) => [$field->label, $field->valueToString($record[$field->name] ?? null)],
            $fields
        );
        // Build rows for ID and Key.
        // If the record doesn't have an ID-field, it's not persisted and that row is omitted.
        $idRows = [['Key', $record['key'] ?? null]];
        if (isset($record['id'])) {
            array_unshift($idRows, ['ID', $record['id'] ?? null]);
        }
        // Add a separator between the rows for id/key and the data fields.
        $rows = [
            ...$idRows,
            new TableSeparator,
            ...$dataRows
        ];
        $this->_renderTable(['Field', 'Value'], $rows, 0);
    }

    /**
     * Render an edited record as table.
     *
     * Display a column for the persisted and another one for the changed values.
     * The first column contains a number for each editable field.
     *
     * @param array<string,mixed> $record Persisted state of the record
     * @param array<string,mixed>|null $newRecord Changed state of the record
     * @return void
     */
    public function renderEditTable(array $record, ?array $newRecord = null): void
    {
        $fields = $this->table->getFields2();
        $dataRows = array_map(
            // TODO better String reprensentation of references, maybe add a method to Schema for returning a default title
            fn (Field $field) => [$field->label, $field->valueToString($record[$field->name] ?? null)],
            $fields
        );

        // Build rows for the data fields (i.e. all fields except id and key).
        $dataRows = [];
        for ($i = 0; $i < sizeof($fields); $i++) {
            $field = $fields[$i];
            $value = $field->valueToString($record[$field->name] ?? null);
            // Build the first three columns: field number, label and original value
            $row = [
                $i + 1,
                $field->label,
                $value,
            ];
            // Add a fourth column for the changed value.
            // Highlight its content if the changed value is different to the original value.
            if ($newRecord) {
                $newValue = $field->valueToString($newRecord[$field->name] ?? null);
                // TODO highlight changed elements in multivalue fields, e.g. in multireference fields
                $format = match (true) {
                    $value === $newValue => '%s',
                    empty($value) && !empty($newValue) => '<fg=green>%s</>',
                    !empty($value) && empty($newValue) => '<fg=red>empty</>', // TODO find a better way to highlight cleared fields
                    $value !== $newValue => '<fg=yellow>%s</>',
                    default => throw new \UnexpectedValueException('This should not happen'),
                };
                $row[] = sprintf($format, $newValue);
            }
            $dataRows[] = $row;
        }

        // Build rows for ID and Key.
        // If the record doesn't have an ID-field, it's not persisted and that row is omitted.
        $idRows = [['0', 'Key', $record['key'] ?? null]];
        if (isset($record['id'])) {
            array_unshift($idRows, ['', 'ID', $record['id'] ?? null]);
        }

        // Add a separator between the rows for id/key and the data fields.
        $rows = [
            ...$idRows,
            new TableSeparator,
            ...$dataRows
        ];

        // Create the column headers.
        $headers = ['#', 'Field', 'Value'];
        if ($newRecord) {
            $headers[] = 'New value';
        }

        $this->_renderTable($headers, $rows, 1);
    }

    /**
     * Display a record summary.
     *
     * @param array<array<mixed>|TableSeparator> $rows
     * @param int $highlightColumn
     * @return void
     */
    protected function _renderTable(array $headers, array $rows, ?int $highlightColumn = null): void
    {
        if (is_int($highlightColumn)) {
            // Highlight the content of a specific column.
            $rows = array_map(
                fn ($row) => is_array($row)
                    ? [
                        ...array_slice($row, 0, $highlightColumn),
                        sprintf('<info>%s</info>', $row[$highlightColumn]),
                        ...array_slice($row, $highlightColumn + 1)
                    ]
                    : $row,
                $rows
            );
        }
        $table = new SymfonyTable($this->output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->setStyle('box');
        $table->render();
        $this->output->newLine();
    }
}
