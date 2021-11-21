<?php

namespace App;

use SleekDB\Store;

class Database
{
    protected array $stores = [];

    public function __construct(protected string $datadir, protected array $configuration)
    {
    }

    public function getPersons(): Store
    {
        return $this->getStore('persons');
    }

    public function getBooks(): Store
    {
        return $this->getStore('books');
    }

    protected function getStore(string $name): Store
    {
        if (!array_key_exists($name, $this->stores)) {
            $this->stores[$name] = new Store($name, $this->datadir, $this->configuration);
        }
        return $this->stores[$name];
    }
}
