<?php

namespace App\Commands\Book;

use App\Commands\ListCommandTrait;
use App\Domain\Repository\BookRepository;
use App\Domain\Type\Book;
use LaravelZero\Framework\Commands\Command;

class ListCommand extends Command
{
    use ListCommandTrait;

    protected $signature = 'book:list
                            {--fields=title,authors : Fields to display, separated by comma}
                            {--orderby= : Field names to order by, separated by comma, may be prefixed with a - for descending sorting}
                            {--groupby= : Field name to group by}
    ';

    protected $description = 'List books';

    public function __construct(
        protected BookRepository $repository,
        protected Book $type,
    ) {
        parent::__construct();
    }

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
        if ($fields && $invalidFields = $this->type->checkFieldNames(...$fields)) {
            $this->error(sprintf('Argument --fields contains invalid fields: %s', implode(', ', $invalidFields)));
            $error = true;
        }
        if ($orderBy && $invalidOrderFields = $this->type->checkFieldNames(...array_keys($orderBy))) {
            $this->error(sprintf('Argument --orderby contains invalid fields: %s', implode(', ', $invalidOrderFields)));
            $error = true;
        }
        if ($groupBy && $this->type->checkFieldNames($groupBy)) {
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

        $records = $this->repository->find($fields, $orderBy);

        $this->renderTable($fields, $records, $hiddenFields, $groupBy);
    }
}
