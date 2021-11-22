<?php

namespace App\Domain\Repository;

use App\Database;
use App\Domain\Type\Book;
use SleekDB\Store;

class BookRepository extends AbstractRepository
{
    public function __construct(protected Database $db, protected Book $type)
    {
    }

    public function getStore(): Store
    {
        return $this->db->books();
    }
}
