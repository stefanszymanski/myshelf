<?php

declare(strict_types=1);

namespace App\Console\Dialog;

use App\Configuration;
use App\Domain\Type\TypeInterface;
use App\Repository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractDialog implements DialogInterface
{
    use DialogTrait;
    use CreateDialogTrait;

    protected Repository $repository;

    protected TypeInterface $type;

    protected string $typeName;

    public function __construct(protected InputInterface $input, protected OutputInterface $output, protected Configuration $configuration)
    {
        // Determine the type name from the class namespace.
        $classNameParts = array_reverse(explode('\\', static::class));
        if ($classNameParts[2] !== 'Dialog') {
            throw new \Exception('Dialogs that extend AbstractDialog must use a namespace like ".../Dialog/{type}/{classname}"');
        }
        $this->typeName = strtolower($classNameParts[1]);
        // Initialize repository and type.
        $this->repository = $this->configuration->getRepository($this->typeName);
        $this->type = $this->configuration->resolveType($this->typeName);
    }

    /**
     * {@inheritDoc}
     */
    abstract public function run(): array;
}
