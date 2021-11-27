<?php declare(strict_types=1);
namespace App\Console\Dialog;

interface DialogInterface
{
    public function run(array $defaults = []): array;
}
