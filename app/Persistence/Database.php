<?php

declare(strict_types=1);

namespace App\Persistence;

use App\Persistence\Schema\Schema;
use LaravelZero\Framework\Application;
use SleekDB\Store;

class Database
{
    public const TABLES = [
        'person',
        'publisher',
        'book',
    ];

    protected array $tables = [];

    public function __construct(
        protected Application $app,
        protected string $datadir,
        protected array $configuration
    ) {
    }

    public function persons(): Table
    {
        return $this->getTable('person');
    }

    public function publishers(): Table
    {
        return $this->getTable('publisher');
    }

    public function books(): Table
    {
        return $this->getTable('book');
    }

    public function getTable(string $name): Table
    {
        if (!in_array($name, self::TABLES)) {
            throw new \InvalidArgumentException("Table '$name' does not exist");
        }
        if (!isset($this->tables[$name])) {
            $schema = $this->resolveSchema($name);
            $store = $this->createStore($name);
            $this->tables[$name] = new Table($this, $schema, $store, $name);
        }
        return $this->tables[$name];
    }

    public function getTables(): array
    {
        return array_map(fn($tableName) => $this->getTable($tableName), self::TABLES);
    }

    protected function createStore(string $name): Store
    {
        return new Store($name, $this->datadir, $this->configuration);
    }

    protected function resolveSchema(string $name): Schema
    {
        $className = sprintf('\\App\\Persistence\\Schema\\%s', ucfirst($name));
        if (!class_exists($className)) {
            throw new \InvalidArgumentException("Type '$className' does not exist");
        }
        return new $className;
    }
}
