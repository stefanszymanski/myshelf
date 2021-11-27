<?php declare(strict_types=1);
namespace App\Console\Dialog;

interface DialogInterface
{
    /**
     * Display the dialog.
     *
     * @param array<string,mixed> $defaults Record defaults
     * @return array<string,mixed> The resulting record
     */
    public function run(array $defaults = []): array;
}
