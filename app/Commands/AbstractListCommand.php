<?php

namespace App\Commands;

use App\Domain\Repository\RepositoryInterface;
use App\Domain\Type\TypeInterface;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;

// TODO refactor to require less boilerplate crap in extending classes
//      maybe replace them by a single ListCommand that handles all types. The
//      same for the AddCommand and other commands in the future.
abstract class AbstractListCommand extends Command
{
    abstract protected function getRepository(): RepositoryInterface;

    abstract protected function getType(): TypeInterface;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $fields = $this->getFields();
        $orderBy = $this->getOrderBy();
        $groupBy = $this->option('groupby');

        // Validate arguments --fields, --orderby and --groupby
        $error = false;
        if ($fields && $invalidFields = $this->getType()->checkFieldNames($fields)) {
            $this->error(sprintf('Argument --fields contains invalid fields: %s', implode(', ', $invalidFields)));
            $error = true;
        }
        if ($orderBy && $invalidOrderFields = $this->getType()->checkFieldNames(array_keys($orderBy))) {
            $this->error(sprintf('Argument --orderby contains invalid fields: %s', implode(', ', $invalidOrderFields)));
            $error = true;
        }
        if ($groupBy && $this->getType()->checkFieldNames([$groupBy])) {
            $this->error(sprintf('Argument --groupby is not a valid field name'));
            $error = true;
        }
        if ($error) {
            return;
        }

        // Build a list of fields that are required to get fetched but should not be displayed.
        $hiddenFields = [];
        if ($groupBy && !in_array($groupBy, $fields)) {
            $fields[] = $groupBy;
            $hiddenFields[] = $groupBy;
        }
        foreach (array_keys($orderBy) as $orderByField) {
            if (!in_array($orderByField, $fields)) {
                $fields[] = $orderByField;
                $hiddenFields[] = $orderByField;
            }
        }
        if (!in_array('id', $fields)) {
            $hiddenFields[] = 'id';
        }

        // TODO validate filter arguments
        // Filters
        $filters = [];
        foreach ($this->option('filter') as $filter) {
            if (preg_match('/^([a-z0-9.-]+)([~=<>!?]+)(.*)$/', $filter, $matches)) {
                array_shift($matches);
                $filters[] = $matches;
            }
        }

        $records = $this->getRepository()->find($fields, $orderBy, $filters, $hiddenFields);

        $this->renderTable($fields, $records, $hiddenFields, $groupBy);
    }

    /**
     * Get the parsed --orderby option.
     *
     * Value of the option is a comma separated list of field names.
     * A field name may be prefixed with ! for a descending sorting.
     *
     * @return array<string,string> Key is a field name, value is either 'asc' or 'desc'
     */
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
        // Field names prefixed with ! are ordered descending.
        $orderBy = [];
        foreach ($_orderBy as $_field) {
            if (str_starts_with($_field, '!')) {
                $orderBy[substr($_field, 1)] = 'desc';
            } else {
                $orderBy[$_field] = 'asc';
            }
        }
        return $orderBy;
    }

    /**
     * Get the parsed --fields option.
     *
     * Value of the option is a comma separated list of field names.
     *
     * @return array<string> List of field names
     */
    protected function getFields(): array
    {
        $_fields = $this->option('fields');
        $fields = array_filter(
            array_map('trim', explode(',', $_fields))
        );
        return $fields;
    }

    protected function renderTable(array $fields, array $records, array $hiddenFields = [], string $groupBy = null)
    {
        $table = new Table($this->output);
        $table->setHeaders($this->getType()->getFieldLabels(array_diff($fields, $hiddenFields)));
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
        // Create rows: a headline for each group and then their rows.
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
