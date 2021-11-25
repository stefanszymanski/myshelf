<?php

namespace App\Commands;

use App\Configuration;
use LaravelZero\Framework\Commands\Command;

class DeleteCommand extends Command
{
    protected $signature = 'rm {type}
                            {--id=* : Record ID}
                            {--key=* : Record Key}
                            {--filter=* : Filter expression: <field><operator><value>}
';

    protected $description = 'Delete one or more records';

    public function __construct(protected Configuration $configuration)
    {
        parent::__construct();
    }

    public function handle()
    {
        // TODO implement: the user must provide either --id, --key or --filter. Combinations are not allowed
        //      When using --filter a summary of affected records is displayed that must be confirmed by the user

        $typeName = $this->argument('type');
        $ids = $this->option('id');

        $repository = $this->configuration->getRepository($typeName);
        foreach ($ids as $id) {
            $repository->getStore()->deleteById($id);
        }
    }
}
