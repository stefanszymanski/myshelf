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
                return null;
            }
            $this->types[$name] = new $className;
        }
        return $this->types[$name];
    }
}
