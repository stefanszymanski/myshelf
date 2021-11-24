<?php

namespace App\Commands\Publisher;

use App\Commands\AbstractListCommand;
use App\Configuration;
use App\Domain\Repository\PublisherRepository;
use App\Domain\Repository\RepositoryInterface;
use App\Domain\Type\TypeInterface;

class ListCommand extends AbstractListCommand
{
    protected $signature = 'publisher:list
                            {--fields=name : Fields to display, separated by comma}
                            {--orderby= : Field names to order by, separated by comma, may be prefixed with a - for descending sorting}
                            {--groupby= : Field name to group by}
                            {--filter=* : Filter expression}
    ';

    protected $description = 'List publishers';

    protected TypeInterface $type;

    public function __construct(
        protected PublisherRepository $repository,
        Configuration $configuration
    ) {
        parent::__construct();
        $this->type = $configuration->getType('publisher');
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

