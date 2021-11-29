<?php

declare(strict_types=1);

namespace App\Persistence;

// TODO rename to .\Query\FieldType
enum FieldType
{
    case Real;
    case Virtual;
    case Joined;
}
