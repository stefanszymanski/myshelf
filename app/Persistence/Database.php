<?php

declare(strict_types=1);

namespace App\Persistence;

use App\Persistence\Schema\Schema;
use LaravelZero\Framework\Application;
use SleekDB\Store;

class Database
{
    /**
     * Names of supported tables
     *
     * @var array<string>
     */
    public const TABLES = [
        'person',
        'publisher',
        'book',
    ];

    /**
     * Cached table objects
     *
     * @var array<Table>
     */
    protected array $tables = [];

    /**
     * @param Application $app
     * @param string $datadir SleekDB data directory
     * @param array<mixed> $configuration SleekDB configuratoin
     */
    public function __construct(
        protected Application $app,
        protected string $datadir,
        protected array $configuration
    ) {
    }

    /**
     * Get the person table.
     *
     * @return Table
     */
    public function persons(): Table
    {
        return $this->getTable('person');
    }

    /**
     * Get the publisher table.
     *
     * @return Table
     */
    public function publishers(): Table
    {
        return $this->getTable('publisher');
    }

    /**
     * Get the book table.
     *
     * @return Table
     */
    public function books(): Table
    {
        return $this->getTable('book');
    }

    /**
     * Get a table by name.
     *
     * @param string $name
     * @return Table
     */
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

    /**
     * Get all tables.
     *
     * @return array<Table>
     */
    public function getTables(): array
    {
        return array_map(fn($tableName) => $this->getTable($tableName), self::TABLES);
    }

    /**
     * Create a SleekDB store.
     *
     * @param string $name
     * @return Store
     */
    protected function createStore(string $name): Store
    {
        return new Store($name, $this->datadir, $this->configuration);
    }

    /**
     * Get a Schema implementation.
     *
     * @param string $name
     * @return Schema
     * @throws \InvalidArgumentException if there is no implementation for table `$name`.
     */
    protected function resolveSchema(string $name): Schema
    {
        $className = sprintf('\\App\\Persistence\\Schema\\%s', ucfirst($name));
        if (!class_exists($className)) {
            throw new \InvalidArgumentException("Type '$className' does not exist");
        }
        return new $className;
    }
}
