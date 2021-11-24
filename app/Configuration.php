<?php

namespace App;

use App\Domain\Type\TypeInterface;

class Configuration
{
    protected array $types = [];

    public function getType(string $name): ?TypeInterface
    {
        if (!isset($this->types[$name])) {
            $className = sprintf('\\App\\Domain\\Type\\%s', ucfirst($name));
            if (!class_exists($className)) {
                throw new \InvalidArgumentException("Type '$name' does not exist");
            }
            $this->types[$name] = new $className;
        }
        return $this->types[$name];
    }
}
