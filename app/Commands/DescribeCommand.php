<?php

namespace App\Commands;

use App\Configuration;
use App\Domain\Type\AbstractType;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;

class DescribeCommand extends Command
{
    const TYPE_LABELS = [
        AbstractType::FIELD_TYPE_REAL => 'Real fields',
        AbstractType::FIELD_TYPE_VIRTUAL => 'Virtual fields, made of values of the same table',
        AbstractType::FIELD_TYPE_JOINED => 'Virtual fields, taken from references on other tables'
    ];

    protected $signature = 'desc {type}';

    protected $description = 'Describe a record type';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(Configuration $configuration)
    {
        $typeName = $this->argument('type');
        $type = $configuration->resolveType($typeName);
        if (!$type) {
            $this->error("Invalid type '$typeName'");
            return;
        }

        // TODO rewrite table rendering
        $info = $type->getFieldInfo();
        $this->output->writeln("\n  Table fields (usable with --fields, --orderby and --groupby)");
        $this->renderTable(['Name', 'Label', 'Description'], $info, 'type');

        $this->output->writeln("\n  Filters (usable with --filter)");
        $info = $type->getFilterInfo();
        usort($info, fn (array $a, array $b) => $a['name'] <=> $b['name']);
        $this->renderTable(['Operator', 'Description'], $info, 'name');
    }

    protected function renderTable(array $headers, array $records, string $groupBy)
    {
        $table = new Table($this->output);
        $table->setHeaders($headers);
        $table->setStyle('box');
        $rows = $this->createGroupedTableRows($records, $groupBy);
        $table->setRows($rows);
        $table->render();
    }

    protected function createGroupedTableRows(array $records, string $groupBy)
    {
        $groupedRows = [];
        $colspan = 0;
        // Split records into groups.
        foreach ($records as $record) {
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
