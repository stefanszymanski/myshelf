<?php

namespace App\Commands\Person;

use App\Database;
use App\Types\Person;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;

class ListCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'person:list
                            {--fields=name : Fields to display, separated by comma}
                            {--orderby= : Field names to order by, separated by comma, may be prefixed with a - for descending sorting}
                            {--groupby= : Field name to group by}
    ';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'List persons';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(Database $db)
    {
        $fields = $this->getFields();
        $select = Person::getSelect($fields);
        $orderBy = $this->getOrderBy();

        $groupBy = $this->option('groupby');
        if ($groupBy && !in_array($groupBy, $fields)) {
            $select[] = $groupBy;
        }

        $queryBuilder = $db->persons()->createQueryBuilder();

        // TODO add virtual field for the number of books
        $queryBuilder->join(function($person) use ($db) {
            return $db->books()->findBy(['authors', 'CONTAINS', $person['key']]);
        }, 'books');
        $select['numberOfBooks'] = ['LENGTH' => 'books'];

        $queryBuilder->select($select);
        $queryBuilder->orderBy($orderBy);

        $persons = $queryBuilder->getQuery()->fetch();

        $this->renderTable($fields, $persons, $groupBy);
    }

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

        if ($this->checkForInvalidFieldNames(array_keys($orderBy))) {
            $this->error("Invalid --orderby value");
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

        if ($this->checkForInvalidFieldNames($fields)) {
            $this->error("Invalid --fields value");
        }
        // Add the id field as as first element, because this field is always returned.
        // TODO is it possible to exclude this field from the query results?
        array_unshift($fields, 'id');

        return $fields;
    }

    protected function checkForInvalidFieldNames(array $fieldnames): bool
    {
        return !empty($fieldnames) && count($fieldnames) !== count(array_intersect($fieldnames, Person::getNames()));
    }

    protected function renderTable(array $fields, array $records, string $groupBy = null)
    {
        $table = new Table($this->output);
        $table->setHeaders(Person::getLabels($fields));
        $table->setStyle('box');
        $rows = $groupBy
            ? $this->createGroupedTableRows($records, $groupBy, !in_array($groupBy, $fields))
            : $records;
        $table->setRows($rows);
        $table->render();
    }

    protected function createGroupedTableRows(array $records, string $groupBy, bool $removeGroupField = true)
    {
        $groupedRows = [];
        $colspan = 0;
        // Split records into groups.
        foreach ($records as $record) {
            $groupValue = $record[$groupBy];
            if (!array_key_exists($groupValue, $groupedRows)) {
                $groupedRows[$groupValue] = [];
            }
            if ($removeGroupField) {
                // It's required to fetch the field that should be sorted by,
                // even if shouldn't show up in the result list. Therefore it
                // gets removed here.
                unset($record[$groupBy]);
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
