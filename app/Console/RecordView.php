<?php

declare(strict_types=1);

namespace App\Console;

use App\Persistence\Field;
use App\Persistence\Table;
use Symfony\Component\Console\Helper\Table as SymfonyTable;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RecordView
{
    public function __construct(protected InputInterface $input, protected SymfonyStyle $output, protected Table $table)
    {
    }

    /**
     * @param array<string,mixed> $record
     * @return void
     */
    public function renderTable(array $record): void
    {
        $fields = $this->table->getFields2();
        $dataRows = array_map(
            fn (Field $field) => [$field->label, $field->valueToString($record[$field->name] ?? null)],
            $fields
        );

        $idRows = [['Key', $record['key'] ?? null]];
        if (isset($record['id'])) {
            array_unshift($idRows, ['ID', $record['id'] ?? null]);
        }

        $rows = [
            ...$idRows,
            new TableSeparator,
            ...$dataRows
        ];
        $this->_renderTable(['Field', 'Value'], $rows, 0);
    }

    public function renderEditTable(array $record, ?array $newRecord = null): void
    {
        // TODO comment code of this class
        // TODO highlight cleared fields for better visibility, currently their values are just empty
        $fields = $this->table->getFields2();
        $dataRows = array_map(
            // TODO better String reprensentation of references, maybe add a method to Schema for returning a default title
            fn (Field $field) => [$field->label, $field->valueToString($record[$field->name] ?? null)],
            $fields
        );

        $dataRows = [];
        for ($i = 0; $i < sizeof($fields); $i++) {
            $field = $fields[$i];
            $value = $field->valueToString($record[$field->name] ?? null);
            $row = [
                $i + 1,
                $field->label,
                $value,
            ];
            if ($newRecord) {
                $newValue = $field->valueToString($newRecord[$field->name] ?? null);
                if ($value !== $newValue) {
                    $newValue = sprintf('<fg=yellow>%s</>', $newValue);
                }
                $row[] = $newValue;
            }
            $dataRows[] = $row;
        }

        $idRows = [['0', 'Key', $record['key'] ?? null]];
        if (isset($record['id'])) {
            array_unshift($idRows, ['', 'ID', $record['id'] ?? null]);
        }

        $rows = [
            ...$idRows,
            new TableSeparator,
            ...$dataRows
        ];

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
