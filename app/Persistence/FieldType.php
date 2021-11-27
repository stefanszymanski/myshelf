<?php

declare(strict_types=1);

namespace App\Persistence;

enum FieldType
{
    case Real;
    case Virtual;
    case Joined;
}
