<?php

declare(strict_types=1);

namespace App\Console\Dialog;

use App\Persistence\Database;
use App\Persistence\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractDialog implements DialogInterface
{
    use DialogTrait;
    use CreateDialogTrait;

    protected string $tableName;

    protected Table $table;

    public function __construct(protected InputInterface $input, protected OutputInterface $output, protected Database $db)
    {
        // Determine the type name from the class namespace.
        $classNameParts = array_reverse(explode('\\', static::class));
        if ($classNameParts[2] !== 'Dialog') {
            throw new \Exception('Dialogs that extend AbstractDialog must use a namespace like ".../Dialog/{type}/{classname}"');
        }
        $this->tableName = strtolower($classNameParts[1]);
        $this->table = $db->getTable($this->tableName);
    }

    /**
     * {@inheritDoc}
     */
    abstract public function run(array $defaults = []): array;
}
