<?php

namespace App;

use SleekDB\Store;

class Database
{
    protected array $stores = [];

    public function __construct(protected string $datadir, protected array $configuration)
    {
    }

    public function persons(): Store
    {
        return $this->getStore('persons');
    }

    public function books(): Store
    {
        return $this->getStore('books');
    }

    public function publishers(): Store
    {
        return $this->getStore('publisher');
    }

    protected function getStore(string $name): Store
    {
        if (!array_key_exists($name, $this->stores)) {
            $this->stores[$name] = new Store($name, $this->datadir, $this->configuration);
        }
        return $this->stores[$name];
    }
}
