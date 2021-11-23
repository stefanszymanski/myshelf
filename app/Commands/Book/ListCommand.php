<?php

namespace App\Commands\Book;

use App\Commands\ListCommandTrait;
use App\Configuration;
use App\Domain\Repository\BookRepository;
use LaravelZero\Framework\Commands\Command;

class ListCommand extends Command
{
    use ListCommandTrait;

    protected $signature = 'book:list
                            {--fields=title,authors : Fields to display, separated by comma}
                            {--orderby= : Field names to order by, separated by comma, may be prefixed with a - for descending sorting}
                            {--groupby= : Field name to group by}
                            {--filter=* : Filter expression}
    ';

    protected $description = 'List books';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(BookRepository $repository, Configuration $configuration)
    {
        $this->type = $configuration->getType('book');

        $fields = $this->getFields();
        $orderBy = $this->getOrderBy();
        $groupBy = $this->option('groupby');

        // Validate arguments --fields, --orderby and --groupby
        $error = false;
        if ($fields && $invalidFields = $this->type->checkFieldNames($fields)) {
            $this->error(sprintf('Argument --fields contains invalid fields: %s', implode(', ', $invalidFields)));
            $error = true;
        }
        if ($orderBy && $invalidOrderFields = $this->type->checkFieldNames(array_keys($orderBy))) {
            $this->error(sprintf('Argument --orderby contains invalid fields: %s', implode(', ', $invalidOrderFields)));
            $error = true;
        }
        if ($groupBy && $this->type->checkFieldNames([$groupBy])) {
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
        // TODO the id field is always fetched and the first field of a record.
        //      Therefore the fields in a record may not be in the same order as in
        //      $fields.
        //      Fix this behaviour!
        if (!in_array('id', $fields)) {
            $hiddenFields[] = 'id';
        }

        // TODO move somewhere else. In general ... this whole method is the same as for persons. I think just one ListCommand is enough.
        //      but I will wait. Maybe there will be differences
        // TODO validate filter arguments
        // Filters
        $filters = [];
        foreach ($this->option('filter') as $filter) {
            // Supported operators are ~ = < > ! ?
            if (preg_match('/^([a-z0-9.-]+)([~=<>!?]+)(.*)$/', $filter, $matches)) {
                array_shift($matches);
                $filters[] = $matches;
            }
        }

        $records = $repository->find($fields, $orderBy, $filters);

        $this->renderTable($fields, $records, $hiddenFields, $groupBy);
    }
}
