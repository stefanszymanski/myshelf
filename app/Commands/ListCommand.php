<?php

namespace App\Commands;

use App\Persistence\Database;
use App\Persistence\Table;
use Illuminate\Support\Arr;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Helper\Table as ConsoleTable;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;

class ListCommand extends Command
{
    protected $signature = 'ls {table : Table name}
                            {--fields= : Fields to display, separated by comma}
                            {--orderby= : Field names to order by, separated by comma, may be prefixed with a ! for descending sorting}
                            {--groupby= : Field name to group by}
                            {--filter=* : Filter expression: <field><operator><value>}
                            {--limit=100 : Number of results to display}
                            {--page= : Page number of results to display, cannot be used with --offset}
                            {--offset= : Number of results to skip, cannot be used with --page}
                            {--format=table : Valid formats are: table, csv, json}
                            {--count : Only print the number of records}
    ';

    protected $description = 'List records';

    protected Table $table;

    public function __construct(protected Database $db)
    {
        parent::__construct();
    }

    // TODO move list rendering to another class
    // TODO implement --format
    // TODO add short version of arguments
    // TODO return error code from all commands when an error occured
    // TODO remove suffix Command from all commands
    public function handle(): void
    {
        $this->table = $this->db->getTable($this->argument('table'));

        $fields = $this->getFields();
        $orderBy = $this->getOrderBy();
        $groupBy = $this->option('groupby');
        $limit = $this->option('limit');
        $page = $this->option('page');
        $offset = $this->option('offset');

        // TODO move argument validation to an earlier point in the command dispatching process, if possible
        // Validate arguments --fields, --orderby and --groupby
        $error = false;
        if ($fields && $invalidFields = $this->table->checkQueryFieldNames($fields)) {
            $this->error(sprintf('Argument --fields contains invalid fields: %s', implode(', ', $invalidFields)));
            $error = true;
        }
        if ($orderBy && $invalidOrderFields = $this->table->checkQueryFieldNames(array_keys($orderBy))) {
            $this->error(sprintf('Argument --orderby contains invalid fields: %s', implode(', ', $invalidOrderFields)));
            $error = true;
        }
        if ($groupBy && $this->table->checkQueryFieldNames([$groupBy])) {
            $this->error(sprintf('Argument --groupby is not a valid field name'));
            $error = true;
        }
        if ($limit < 1) {
            $this->error('Argument --limit must be an integer greater 0');
            $error = true;
        }
        if ($page !== null && $page < 1) {
            $this->error('Argument --page must be an integer greater 0');
            $error = true;
        }
        if ($offset !== null && $offset < 0) {
            $this->error('Argument --offset must be an integer greater or equal 0');
            $error = true;
        }
        if ($offset !== null && $page !== null) {
            $this->error('Arguments --offset and --page cannot be used together');
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

        $exceptFields = [];
        if (!in_array('id', $fields)) {
            $exceptFields[] = 'id';
        }

        // TODO validate filter arguments
        // Filters
        $filters = [];
        foreach ($this->option('filter') as $filter) {
            if (preg_match('/^([a-z0-9.:-]+)([~=<>!?#]+)(.*)$/', $filter, $matches)) {
                array_shift($matches);
                $filters[] = $matches;
            }
        }

        $limit = (int) $limit;
        $offset = match (true) {
            !$offset && !$page => 0,
            $offset !== null => (int) $offset,
            default => $limit * ((int) $page - 1),
        };
        $records = $this->table->find($fields, $orderBy, $filters, $exceptFields, $limit, $offset);

        if ($this->option('count')) {
            $this->output->writeln((string) count($records));
        } else {
            $records = array_map(Arr::dot(...), $records);
            $headers = $this->table->getQueryFieldLabels(array_diff($fields, $hiddenFields));
            $this->renderTable($headers, $records, $hiddenFields, $groupBy);
        }
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
        if (empty($_fields)) {
            $fields = $this->table->getDefaultListQueryFields();
        } else {
            $fields = array_filter(
                array_map('trim', explode(',', $_fields))
            );
        }
        return $fields;
    }

    /**
     * Display a table for the given records.
     *
     * @param array<string> $headers
     * @param array<array<string,mixed>> $records Records to display
     * @param array<string> $hiddenFields Fields that may be included in the records, but should not be displayed
     * @param string|null $groupBy Field name to group by
     * @return void
     */
    protected function renderTable(array $headers, array $records, array $hiddenFields = [], string $groupBy = null): void
    {
        $table = new ConsoleTable($this->output);
        $table->setHeaders($headers);
        $table->setStyle('box');
        $table->getStyle()->setCellHeaderFormat('<comment>%s</comment>');
        $rows = $groupBy
            ? $this->createGroupedTableRows($records, $groupBy, $hiddenFields)
            : $records;
        $table->setRows($rows);
        $table->render();
    }

    /**
     * Group the given records and create table rows with group headers.
     *
     * @param array<array<string,mixed>> $records
     * @param string $groupBy
     * @param array<string> $hiddenFields Fields that may be included in the records, but should not be displayed
     * @return array<array<mixed>|TableCell>
     */
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
            $rows[] = [new TableCell("<info>$groupValue</info>", ['colspan' => $colspan])];
            $rows[] = $separator;
            $rows = array_merge($rows, $_rows);
            $groupCounter++;
        }
        return $rows;
    }
}
