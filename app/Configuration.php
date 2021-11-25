<?php

namespace App;

use App\Repository;
use App\Domain\Type\TypeInterface;
use LaravelZero\Framework\Application;

class Configuration
{
    protected array $types = [];

    protected array $repositories = [];

    public function __construct(protected Application $app)
    {
    }

    public function resolveType(string $name): TypeInterface
    {
        if (!isset($this->types[$name])) {
            $className = sprintf('\\App\\Domain\\Type\\%s', ucfirst($name));
            if (!class_exists($className)) {
                throw new \InvalidArgumentException("Type '$className' does not exist");
            }
            $this->types[$name] = new $className;
        }
        return $this->types[$name];
    }

    public function getRepository(string $type): Repository
    {
        if (!isset($this->repository[$type])) {
            /* $className = sprintf('\\App\\Domain\\Repository\\%sRepository', ucfirst($type)); */
            /* if (!class_exists($className)) { */
            /*     throw new \InvalidArgumentException("Repository '$className' does not exist"); */
            /* } */
            $db = $this->app->make(Database::class);
            $this->repositories[$type] = new Repository($db->getStore($type), $this->resolveType($type), $db);
        }
        return $this->repositories[$type];
    }
}
