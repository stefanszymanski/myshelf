<?php

declare(strict_types=1);

namespace App\Console\Dialog\Publisher;

use App\Console\Dialog\AbstractDialog;
use App\Utility\RecordUtility;

class CreateDialog extends AbstractDialog
{
    /**
     * {@inheritDoc}
     */
    public function run(array $defaults = []): array
    {
        $labels = $this->table->getFieldLabels(['key', 'name']);

        $name = $this->askMandatory($labels['name'], $defaults['name'] ?? null);
        $defaultKey = RecordUtility::createKey($name);
        $key = $this->askForKey($labels['key'], $defaultKey);

        return [
            'key' => $key,
            'name' => $name,
        ];
    }
}

