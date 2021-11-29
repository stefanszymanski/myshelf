<?php

declare(strict_types=1);

namespace App\Persistence\Query;

enum FieldType
{
    case Real;
    case Virtual;
    case Joined;
}
