<?php

namespace App\Domain\Repository;

use App\Database;
use App\Domain\Type\Person;
use SleekDB\Store;

class PersonRepository extends AbstractRepository
{
    public function __construct(protected Database $db, protected Person $type)
    {
    }

    public function getStore(): Store
    {
        return $this->db->persons();
    }
}
