<?php

namespace App\Commands;

use App\Persistence\Database;
use App\Persistence\Query\Field as QueryField;
use App\Persistence\Query\Filter as QueryFilter;
use App\Persistence\Query\FieldType as QueryFieldType;
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
        $info = array_map((function (QueryField $field) {
            return [
                'name' => $field->name,
                'label' => $field->label,
                'description' => $field->description,
                'type' => $this->getFieldTypeLabel($field->type),
            ];
        })->bindTo($this), $table->getFields());
        $this->output->writeln("\n  Table fields (usable with --fields, --orderby and --groupby)");
        $this->renderTable(['Name', 'Label', 'Description'], $info, 'type');

        $this->output->writeln("\n  Filters (usable with --filter)");
        $info = array_map(function (QueryFilter $filter) {
            return [
                'name' => $filter->field,
                'operator' => $filter->operator,
                'description' => $filter->description,
            ];
        }, $table->getFilters());
        usort($info, fn (array $a, array $b) => $a['name'] <=> $b['name']);
        $this->renderTable(['Operator', 'Description'], $info, 'name');
    }

    protected function renderTable(array $headers, array $rows, string $groupBy): void
    {
        $table = new Table($this->output);
        $table->setHeaders($headers);
        $table->setStyle('box');
        $rows = $this->createGroupedTableRows($rows, $groupBy);
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

    protected function getFieldTypeLabel(QueryFieldType $type): string
    {
        return match ($type) {
            QueryFieldType::Real => 'Real fields',
            QueryFieldType::Virtual => 'Virtual fields, made of values of the same table',
            QueryFieldType::Joined => 'Virtual fields, taken from references on other tables',
        };
    }
}
