<?php

namespace App\Commands;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;

trait ListCommandTrait
{
    protected function getOrderBy(): array
    {
        $_orderBy = $this->option('orderby');
        if (!$_orderBy) {
            return [];
        }

        $_orderBy = array_filter(
            array_map('trim', explode(',', $_orderBy))
        );

        // Parse values.
        // Field names prefixed with - are ordered descending.
        // Field names prefixed with + or without prefix are ordeded ascending.
        $orderBy = [];
        foreach ($_orderBy as $_field) {
            if (str_starts_with($_field, '-')) {
                $orderBy[substr($_field, 1)] = 'desc';
            } elseif (str_starts_with($_field, '+')) {
                $orderBy[substr($_field, 1)] = 'asc';
            } else {
                $orderBy[$_field] = 'asc';
            }
        }

        return $orderBy;
    }

    protected function getFields(): array
    {
        $_fields = $this->option('fields');
        // Trim whitespaces around field names.
        $fields = array_filter(
            array_map('trim', explode(',', $_fields))
        );

        return $fields;
    }

    protected function renderTable(array $fields, array $records, array $hiddenFields = [], string $groupBy = null)
    {
        $table = new Table($this->output);
        $table->setHeaders($this->type->getFieldLabels(...array_diff($fields, $hiddenFields)));
        $table->setStyle('box');
        $rows = $groupBy
            ? $this->createGroupedTableRows($records, $groupBy, $hiddenFields)
            : $records;
        $table->setRows($rows);
        $table->render();
    }

    protected function createGroupedTableRows(array $records, string $groupBy, array $hiddenFields = [])
    {
        $groupedRows = [];
        $colspan = 0;
        // Split records into groups.
        foreach ($records as $record) {
            $groupValue = $record[$groupBy];
            if (!array_key_exists($groupValue, $groupedRows)) {
                $groupedRows[$groupValue] = [];
            }
            foreach ($hiddenFields as $hiddenField) {
                unset($record[$hiddenField]);
            }
            $groupedRows[$groupValue][] = $record;
            $colspan = $colspan >= count($record) ? $colspan : count($record);
        }
        // Create rows: a headline for each group and than their rows.
        $rows = [];
        $separator = new TableSeparator();
        $groupCounter = 0;
        foreach ($groupedRows as $groupValue => $_rows) {
            if ($groupCounter > 0) {
                // Don't add a separator before the first group headline.
                $rows[] = $separator;
            }
            $rows[] = [new TableCell($groupValue, ['colspan' => $colspan])];
            $rows[] = $separator;
            $rows = array_merge($rows, $_rows);
            $groupCounter++;
        }
        return $rows;
    }
}
