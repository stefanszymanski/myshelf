<?php

namespace App\Commands;

use App\Persistence\Database;
use App\Persistence\Query\Field as QueryField;
use App\Persistence\Query\Filter as QueryFilter;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;

// TODO refactor or rewrite
class DescribeCommand extends Command
{
    protected $signature = 'desc {table}';

    protected $description = 'Describe a record type';

    public function __construct(protected Database $db)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $tableName = $this->argument('table');
        $table = $this->db->getTable($tableName);

        // TODO rewrite table rendering
        $fields = $table->getQueryFields();
        $info = collect($fields)
            ->map(fn(QueryField $field, string $fieldName) => [
                'name' => $fieldName,
                'label' => $field->getLabel($fieldName, null, $this->db, $table),
            ])
            ->all();
        $this->output->writeln("\n  Table fields (usable with --fields, --orderby and --groupby)");
        $this->renderTable(['Name', 'Label'], $info);

        // TODO implement info about query filters
        /* $info = array_map(function (QueryFilter $filter) { */
        /*     return [ */
        /*         'name' => $filter->field, */
        /*         'operator' => $filter->operator, */
        /*     ]; */
        /* }, $table->getQueryFilters()); */
        /* usort($info, fn (array $a, array $b) => $a['name'] <=> $b['name']); */
        /* $this->output->writeln("\n  Filters (usable with --filter)"); */
        /* $this->renderTable(['Operator', 'Description'], $info, 'name'); */
    }

    protected function renderTable(array $headers, array $rows): void
    {
        $table = new Table($this->output);
        $table->setStyle('box');
        $table->getStyle()->setCellHeaderFormat('<thead>%s</>');
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
    }

    protected function createGroupedTableRows(array $rows, string $groupBy): array
    {
        $groupedRows = [];
        $colspan = 0;
        // Split records into groups.
        foreach ($rows as $record) {
            $groupValue = $record[$groupBy];
            if (!array_key_exists($groupValue, $groupedRows)) {
                $groupedRows[$groupValue] = [];
            }
            unset($record[$groupBy]);
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
            /* $rows[] = [new TableCell(self::TYPE_LABELS[$groupValue], ['colspan' => $colspan])]; */
            $rows[] = [new TableCell($groupValue, ['colspan' => $colspan])];
            $rows[] = $separator;
            $rows = array_merge($rows, $_rows);
            $groupCounter++;
        }
        return $rows;
    }
}
