<?php

namespace App;

use SleekDB\Store;

class Database
{
    protected const STORES = [
        'person',
        'publisher',
        'book'
    ];

    protected array $stores = [];

    public function __construct(protected string $datadir, protected array $configuration)
    {
    }

    public function persons(): Store
    {
        return $this->getStore('person');
    }

    public function books(): Store
    {
        return $this->getStore('book');
    }

    public function publishers(): Store
    {
        return $this->getStore('publisher');
    }

    public function getStore(string $name): Store
    {
        if (!array_key_exists($name, $this->stores)) {
            if (!in_array($name, self::STORES)) {
                throw new \InvalidArgumentException("Store '$name' does not exist");
            }
            $this->stores[$name] = new Store($name, $this->datadir, $this->configuration);
        }
        return $this->stores[$name];
    }
}
