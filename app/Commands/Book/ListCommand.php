<?php

namespace App\Commands\Book;

use App\Commands\AbstractListCommand;
use App\Configuration;
use App\Domain\Repository\BookRepository;
use App\Domain\Repository\RepositoryInterface;
use App\Domain\Type\TypeInterface;

class ListCommand extends AbstractListCommand
{
    protected $signature = 'book:list
                            {--fields=title,authors : Fields to display, separated by comma}
                            {--orderby= : Field names to order by, separated by comma, may be prefixed with a - for descending sorting}
                            {--groupby= : Field name to group by}
                            {--filter=* : Filter expression}
    ';

    protected $description = 'List books';

    protected TypeInterface $type;

    public function __construct(
        protected BookRepository $repository,
        Configuration $configuration
    ) {
        parent::__construct();
        $this->type = $configuration->getType('book');
    }

    protected function getRepository(): RepositoryInterface
    {
        return $this->repository;
    }

    protected function getType(): TypeInterface
    {
        return $this->type;
    }

}
